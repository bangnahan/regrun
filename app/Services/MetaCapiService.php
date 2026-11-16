<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCapiService
{
    /**
     * Send server-side Purchase event to Meta Conversions API (CAPI).
     *
     * @return array<string, mixed>
     */
    public function sendPurchaseEvent(Transaction $transaction, bool $force = false): array
    {
        $event = $transaction->event;

        if (! $event || ! $event->hasMetaCapi()) {
            return [
                'success' => false,
                'skipped' => true,
                'reason' => 'Meta CAPI is not configured for this event (pixel_id or capi_token missing).',
            ];
        }

        // Avoid duplicate Purchase transmissions unless forced
        if ($transaction->capi_purchase_sent_at && ! $force) {
            return [
                'success' => true,
                'skipped' => true,
                'reason' => 'Purchase event was already sent to Meta CAPI at '.$transaction->capi_purchase_sent_at->toIso8601String(),
            ];
        }

        $pixelId = trim((string) $event->meta_pixel_id);
        $accessToken = trim((string) $event->meta_capi_token);

        // Prepare User Data with Meta Hashing Specifications
        $userData = [
            'em' => [$this->hashEmail($transaction->buyer_email)],
            'ph' => [$this->hashPhone($transaction->buyer_phone)],
        ];

        [$firstNameHash, $lastNameHash] = $this->hashName($transaction->buyer_name);
        if ($firstNameHash) {
            $userData['fn'] = [$firstNameHash];
        }
        if ($lastNameHash) {
            $userData['ln'] = [$lastNameHash];
        }

        if (! empty($transaction->ip_address)) {
            $userData['client_ip_address'] = $transaction->ip_address;
        }

        if (! empty($transaction->user_agent)) {
            $userData['client_user_agent'] = $transaction->user_agent;
        }

        if (! empty($transaction->meta_fbp)) {
            $userData['fbp'] = $transaction->meta_fbp;
        }

        if (! empty($transaction->meta_fbc)) {
            $userData['fbc'] = $transaction->meta_fbc;
        }

        // Prepare Item Contents
        $contents = [];
        foreach ($transaction->items as $item) {
            $cat = $item->ticketCategory;
            $contents[] = [
                'id' => $cat?->code ?? ('CAT-'.$item->ticket_category_id),
                'quantity' => (int) $item->quantity,
                'item_price' => (float) $item->unit_price,
            ];
        }

        // Count jersey surcharge if applicable
        $longSleeveCount = $transaction->participants->where('jersey_type', 'long')->count();
        if ($longSleeveCount > 0) {
            $surchargePrice = (float) ($event->long_sleeve_price ?? 20000);
            $contents[] = [
                'id' => 'JERSEY-LONG-SLEEVE',
                'quantity' => $longSleeveCount,
                'item_price' => $surchargePrice,
            ];
        }

        $eventSourceUrl = url('/order/'.$transaction->invoice_number);

        $eventPayload = [
            'event_name' => 'Purchase',
            'event_time' => $transaction->paid_at ? $transaction->paid_at->timestamp : time(),
            'event_id' => $transaction->invoice_number,
            'event_source_url' => $eventSourceUrl,
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => [
                'currency' => 'IDR',
                'value' => (float) $transaction->subtotal,
                'content_type' => 'product',
                'contents' => $contents,
                'order_id' => $transaction->invoice_number,
                'num_items' => $transaction->items->sum('quantity'),
            ],
        ];

        $requestBody = [
            'data' => [$eventPayload],
        ];

        // Add Test Event Code if provided in Event settings
        if (! empty($event->meta_capi_test_code)) {
            $requestBody['test_event_code'] = trim($event->meta_capi_test_code);
        }

        try {
            $endpoint = "https://graph.facebook.com/v21.0/{$pixelId}/events";
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($endpoint, $requestBody);

            if ($response->successful()) {
                $transaction->update([
                    'capi_purchase_sent_at' => now(),
                    'capi_purchase_response' => $response->body(),
                ]);

                Log::info("Meta CAPI: Purchase sent successfully for invoice {$transaction->invoice_number}", [
                    'pixel_id' => $pixelId,
                    'response' => $response->json(),
                ]);

                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'data' => $response->json(),
                ];
            }

            $errorMessage = $response->body();
            $transaction->update([
                'capi_purchase_response' => 'FAILED ('.$response->status().'): '.$errorMessage,
            ]);

            Log::warning("Meta CAPI: Failed sending purchase for invoice {$transaction->invoice_number}", [
                'status' => $response->status(),
                'response' => $errorMessage,
            ]);

            return [
                'success' => false,
                'status_code' => $response->status(),
                'error' => $errorMessage,
            ];
        } catch (\Throwable $e) {
            Log::error("Meta CAPI Exception for invoice {$transaction->invoice_number}: ".$e->getMessage());

            $transaction->update([
                'capi_purchase_response' => 'EXCEPTION: '.$e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize and SHA-256 hash an email address.
     */
    public function hashEmail(?string $email): string
    {
        if (empty($email)) {
            return '';
        }

        return hash('sha256', trim(strtolower($email)));
    }

    /**
     * Normalize and SHA-256 hash an Indonesian / International phone number.
     * Replaces leading 08 with 628, removes all non-numeric characters.
     */
    public function hashPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '';
        }

        $cleaned = preg_replace('/[^0-9]/', '', $phone) ?? '';

        if (str_starts_with($cleaned, '08')) {
            $cleaned = '628'.substr($cleaned, 2);
        } elseif (str_starts_with($cleaned, '8')) {
            $cleaned = '62'.$cleaned;
        }

        return hash('sha256', $cleaned);
    }

    /**
     * Normalize and SHA-256 hash first and last names.
     *
     * @return array{0: string, 1: string}
     */
    public function hashName(?string $fullName): array
    {
        if (empty($fullName)) {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', trim(strtolower($fullName))) ?: [];
        $firstName = $parts[0] ?? '';
        $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        return [
            $firstName ? hash('sha256', $firstName) : '',
            $lastName ? hash('sha256', $lastName) : '',
        ];
    }
}
