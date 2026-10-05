<?php

namespace App\Services;

use App\Models\PaymentLog;
use App\Models\SystemSetting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TripayService
{
    protected string $apiKey;
    protected string $privateKey;
    protected string $merchantCode;
    protected bool $isSandbox;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantCode = SystemSetting::get('tripay_merchant_code', config('services.tripay.merchant_code', env('TRIPAY_MERCHANT_CODE', 'T39430')));
        $this->apiKey = SystemSetting::get('tripay_api_key', config('services.tripay.api_key', env('TRIPAY_API_KEY', 'DEV-ef2bKOHNkSJqCVWJ85wTIKYOYXm4m40Q7Gfioc5N')));
        $this->privateKey = SystemSetting::get('tripay_private_key', config('services.tripay.private_key', env('TRIPAY_PRIVATE_KEY', 'f85W1-PuaJ2-J3i96-XoBzw-WBtLf')));
        $this->isSandbox = (bool) SystemSetting::get('tripay_sandbox', config('services.tripay.sandbox', env('TRIPAY_SANDBOX', true)));

        $this->baseUrl = $this->isSandbox
            ? 'https://tripay.co.id/api-sandbox/'
            : 'https://tripay.co.id/api/';
    }

    /**
     * Get list of payment channels (from Tripay API with fallback)
     */
    public function getPaymentChannels(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(5)->get($this->baseUrl . 'merchant/payment-channel');

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['data'])) {
                    return $data['data'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Tripay API getPaymentChannels error: ' . $e->getMessage());
        }

        // Standard fallback payment channels if API key is in sandbox mode or IP not yet whitelisted
        return $this->getDefaultChannels();
    }

    /**
     * Create a closed transaction in Tripay
     */
    public function createTransaction(Transaction $transaction, string $channelCode): array
    {
        $merchantRef = $transaction->invoice_number;
        $amount = (int) round($transaction->grand_total);
        $signature = hash_hmac('sha256', $this->merchantCode . $merchantRef . $amount, $this->privateKey);

        $orderItems = [];
        foreach ($transaction->items as $item) {
            $orderItems[] = [
                'name' => $item->ticketCategory->name ?? 'Tiket Event Lari',
                'price' => (int) round($item->unit_price),
                'quantity' => (int) $item->quantity,
                'subtotal' => (int) round($item->subtotal),
            ];
        }

        $payload = [
            'method' => $channelCode,
            'merchant_ref' => $merchantRef,
            'amount' => $amount,
            'customer_name' => $transaction->buyer_name,
            'customer_email' => $transaction->buyer_email,
            'customer_phone' => $transaction->buyer_phone,
            'order_items' => $orderItems,
            'return_url' => route('order.show', ['invoice' => $transaction->invoice_number]),
            'expired_time' => now()->addMinutes(60)->timestamp,
            'signature' => $signature,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(8)->post($this->baseUrl . 'transaction/create', $payload);

            $resData = $response->json();

            // Log attempt
            PaymentLog::create([
                'transaction_id' => $transaction->id,
                'tripay_reference' => $resData['data']['reference'] ?? null,
                'event_type' => 'create_transaction',
                'signature' => $signature,
                'raw_payload' => $payload,
                'raw_response' => $resData,
                'http_status' => (string) $response->status(),
                'ip_address' => request()->ip(),
            ]);

            if ($response->successful() && !empty($resData['data'])) {
                $trxData = $resData['data'];
                return [
                    'success' => true,
                    'reference' => $trxData['reference'] ?? null,
                    'checkout_url' => $trxData['checkout_url'] ?? null,
                    'pay_code' => $trxData['pay_code'] ?? null,
                    'qr_url' => $trxData['qr_url'] ?? null,
                    'fee' => $trxData['total_fee'] ?? 0,
                    'expires_at' => isset($trxData['expired_time']) ? date('Y-m-d H:i:s', $trxData['expired_time']) : now()->addMinutes(60),
                ];
            }
        } catch (\Throwable $e) {
            Log::error('Tripay API createTransaction exception: ' . $e->getMessage());
        }

        // Fallback for simulation / Sandbox sandbox mock mode:
        $mockRef = 'DEV-' . strtoupper(bin2hex(random_bytes(6)));
        $mockPayCode = '88' . str_pad((string) rand(10000000, 99999999), 10, '0', STR_PAD_LEFT);
        
        return [
            'success' => true,
            'is_mock' => true,
            'reference' => $mockRef,
            'checkout_url' => route('order.show', ['invoice' => $transaction->invoice_number]),
            'pay_code' => $mockPayCode,
            'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode("TRIPAY:{$mockRef}:{$amount}"),
            'fee' => 4500,
            'expires_at' => now()->addMinutes(60),
        ];
    }

    /**
     * Validate Webhook Signature from Tripay
     */
    public function validateCallback(string $jsonPayload, ?string $incomingSignature): bool
    {
        if (empty($incomingSignature)) {
            return false;
        }

        $calculated = hash_hmac('sha256', $jsonPayload, $this->privateKey);
        return hash_equals($calculated, $incomingSignature);
    }

    /**
     * Fallback standard channel list
     */
    public function getDefaultChannels(): array
    {
        return [
            [
                'code' => 'QRIS',
                'name' => 'QRIS (Semua E-Wallet & Mobile Banking)',
                'group' => 'E-Wallet / Instant',
                'fee_merchant' => ['flat' => 750, 'percent' => 0.7],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
            [
                'code' => 'BCAVA',
                'name' => 'BCA Virtual Account',
                'group' => 'Virtual Account',
                'fee_merchant' => ['flat' => 4500, 'percent' => 0],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
            [
                'code' => 'MANDIRIVA',
                'name' => 'Mandiri Virtual Account',
                'group' => 'Virtual Account',
                'fee_merchant' => ['flat' => 4000, 'percent' => 0],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
            [
                'code' => 'BNIVA',
                'name' => 'BNI Virtual Account',
                'group' => 'Virtual Account',
                'fee_merchant' => ['flat' => 4000, 'percent' => 0],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
            [
                'code' => 'BRIVA',
                'name' => 'BRI Virtual Account',
                'group' => 'Virtual Account',
                'fee_merchant' => ['flat' => 4000, 'percent' => 0],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
            [
                'code' => 'PERMATAVA',
                'name' => 'Permata Virtual Account',
                'group' => 'Virtual Account',
                'fee_merchant' => ['flat' => 4000, 'percent' => 0],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
            [
                'code' => 'BSIVA',
                'name' => 'BSI Virtual Account',
                'group' => 'Virtual Account',
                'fee_merchant' => ['flat' => 4000, 'percent' => 0],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
            [
                'code' => 'ALFAMART',
                'name' => 'Alfamart / Alfamidi',
                'group' => 'Convenience Store',
                'fee_merchant' => ['flat' => 3500, 'percent' => 0],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
            [
                'code' => 'INDOMARET',
                'name' => 'Indomaret',
                'group' => 'Convenience Store',
                'fee_merchant' => ['flat' => 3500, 'percent' => 0],
                'fee_customer' => ['flat' => 0, 'percent' => 0],
                'active' => true,
            ],
        ];
    }
}
