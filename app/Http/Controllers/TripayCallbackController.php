<?php

namespace App\Http\Controllers;

use App\Models\PaymentLog;
use App\Models\Transaction;
use App\Services\MailketingService;
use App\Services\TripayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TripayCallbackController extends Controller
{
    protected TripayService $tripayService;
    protected MailketingService $mailketingService;

    public function __construct(TripayService $tripayService, MailketingService $mailketingService)
    {
        $this->tripayService = $tripayService;
        $this->mailketingService = $mailketingService;
    }

    /**
     * Handle incoming webhook notification from Tripay
     */
    public function handle(Request $request): JsonResponse
    {
        $rawPayload = $request->getContent();
        $signature = $request->header('X-Callback-Signature');
        $event = $request->header('X-Callback-Event');

        // Log raw webhook
        PaymentLog::create([
            'event_type' => 'webhook_received',
            'signature' => $signature,
            'raw_payload' => json_decode($rawPayload, true),
            'http_status' => '200',
            'ip_address' => $request->ip(),
        ]);

        // 1. Verify HMAC Signature
        if (!$this->tripayService->validateCallback($rawPayload, $signature)) {
            Log::warning('Tripay Webhook invalid signature from IP: ' . $request->ip());
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        $data = json_decode($rawPayload, true);
        if (!$data || empty($data['merchant_ref'])) {
            return response()->json(['success' => false, 'message' => 'Invalid payload format'], 400);
        }

        // Find transaction
        $transaction = Transaction::where('invoice_number', $data['merchant_ref'])
            ->orWhere('tripay_merchant_ref', $data['merchant_ref'])
            ->first();

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $status = strtoupper($data['status'] ?? '');

        // Handle PAID status
        if ($status === 'PAID') {
            if ($transaction->status !== 'PAID') {
                $transaction->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                    'tripay_reference' => $data['reference'] ?? $transaction->tripay_reference,
                ]);

                // Shift quota from reserved to sold
                foreach ($transaction->items as $item) {
                    $cat = $item->ticketCategory;
                    $cat->decrement('reserved_count', $item->quantity);
                    $cat->increment('sold_count', $item->quantity);
                }

                // Generate BIB numbers if auto_generate_bib is enabled
                $shouldAutoGenerate = $transaction->event?->auto_generate_bib ?? true;
                if ($shouldAutoGenerate) {
                    foreach ($transaction->participants as $p) {
                        if (!$p->bib_number) {
                            $codePrefix = substr($p->ticketCategory->code, 0, 2);
                            $p->update([
                                'bib_number' => $codePrefix . str_pad((string) $p->id, 4, '0', STR_PAD_LEFT),
                            ]);
                        }
                    }
                }

                // Send emails via Mailketing
                try {
                    $this->mailketingService->sendInvoiceEmail($transaction);
                    foreach ($transaction->participants as $participant) {
                        $this->mailketingService->sendTicketEmail($participant);
                    }
                } catch (\Throwable $e) {
                    Log::error('Mailketing webhook trigger error: ' . $e->getMessage());
                }
            }
        } elseif (in_array($status, ['EXPIRED', 'FAILED'])) {
            if ($transaction->status === 'UNPAID') {
                $transaction->update(['status' => $status]);

                // Release reserved quota
                foreach ($transaction->items as $item) {
                    $item->ticketCategory->decrement('reserved_count', $item->quantity);
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
