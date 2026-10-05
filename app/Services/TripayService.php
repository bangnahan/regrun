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
        $this->apiKey = SystemSetting::get('tripay_api_key', config('services.tripay.api_key', env('TRIPAY_API_KEY', 'DEV-KTItaLxH6EY0VqEkbWrPFgkM8yunO9Btd7bMmNMi')));
        $this->privateKey = SystemSetting::get('tripay_private_key', config('services.tripay.private_key', env('TRIPAY_PRIVATE_KEY', 'yNQJm-Ozybz-wRDDa-ncqiY-PZ280')));
        $this->isSandbox = (bool) SystemSetting::get('tripay_sandbox', config('services.tripay.sandbox', env('TRIPAY_SANDBOX', true)));

        $this->baseUrl = $this->isSandbox
            ? 'https://tripay.co.id/api-sandbox/'
            : 'https://tripay.co.id/api/';
    }

    /**
     * Get list of payment channels from Tripay API with caching & complete fallback
     */
    public function getPaymentChannels(): array
    {
        return cache()->remember('tripay_payment_channels_' . md5($this->apiKey), 300, function () {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->timeout(8)->get($this->baseUrl . 'merchant/payment-channel');

                if ($response->successful()) {
                    $data = $response->json();
                    if (!empty($data['data']) && is_array($data['data'])) {
                        // Filter only active channels
                        $activeChannels = array_filter($data['data'], fn($c) => !empty($c['active']));
                        if (!empty($activeChannels)) {
                            return array_values($activeChannels);
                        }
                    }
                } else {
                    Log::warning('Tripay getPaymentChannels returned HTTP ' . $response->status() . ': ' . $response->body());
                }
            } catch (\Throwable $e) {
                Log::warning('Tripay API getPaymentChannels error: ' . $e->getMessage());
            }

            return $this->getDefaultChannels();
        });
    }

    /**
     * Test connection to Tripay API
     */
    public function testConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(8)->get($this->baseUrl . 'merchant/payment-channel');

            if ($response->successful()) {
                $data = $response->json();
                $channels = $data['data'] ?? [];
                return [
                    'connected' => true,
                    'status' => 'OK',
                    'merchant_code' => $this->merchantCode,
                    'mode' => $this->isSandbox ? 'Sandbox' : 'Production',
                    'channel_count' => count($channels),
                    'channels' => $channels,
                ];
            }

            return [
                'connected' => false,
                'status' => 'HTTP ' . $response->status(),
                'error' => $response->json('message') ?? $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'status' => 'EXCEPTION',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a closed transaction in Tripay
     */
    public function createTransaction(Transaction $transaction, string $channelCode): array
    {
        // Sandbox failsafe: If merchant has QRIS2 activated, map QRIS -> QRIS2
        if (strtoupper($channelCode) === 'QRIS' && $this->isSandbox) {
            $channelCode = 'QRIS2';
        }

        $merchantRef = $transaction->invoice_number;
        // Amount sent to Tripay is the pure order subtotal; Tripay calculates & appends customer fee
        $amount = (int) round($transaction->subtotal);
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

        // Determine callback & return URLs
        $callbackUrl = env('TRIPAY_CALLBACK_URL');
        if (empty($callbackUrl)) {
            $appUrl = config('app.url', 'http://127.0.0.1:8080');
            if (!str_contains($appUrl, 'localhost') && !str_contains($appUrl, '127.0.0.1')) {
                $callbackUrl = rtrim($appUrl, '/') . '/api/tripay/callback';
            }
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
            'expired_time' => now()->addMinutes(120)->timestamp,
            'signature' => $signature,
        ];

        if (!empty($callbackUrl)) {
            $payload['callback_url'] = $callbackUrl;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->timeout(12)->post($this->baseUrl . 'transaction/create', $payload);

            $resData = $response->json();

            // Log raw communication
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
                    'fee' => (int) ($trxData['total_fee'] ?? 0),
                    'amount' => (int) ($trxData['amount'] ?? ($amount + ($trxData['total_fee'] ?? 0))),
                    'instructions' => $trxData['instructions'] ?? [],
                    'expires_at' => isset($trxData['expired_time']) ? date('Y-m-d H:i:s', $trxData['expired_time']) : now()->addMinutes(120),
                ];
            }

            $errMsg = $resData['message'] ?? 'Tripay mengembalikan kode status ' . $response->status();
            Log::error('Tripay createTransaction error: ' . $errMsg, ['payload' => $payload, 'response' => $resData]);
        } catch (\Throwable $e) {
            $errMsg = $e->getMessage();
            Log::error('Tripay API createTransaction exception: ' . $errMsg);
        }

        // Testing environment mock fallback only
        if (app()->environment('testing')) {
            $mockRef = 'DEV-TEST-' . strtoupper(bin2hex(random_bytes(4)));
            $mockPayCode = '88' . str_pad((string) rand(10000000, 99999999), 10, '0', STR_PAD_LEFT);

            return [
                'success' => true,
                'is_mock' => true,
                'reference' => $mockRef,
                'checkout_url' => route('order.show', ['invoice' => $transaction->invoice_number]),
                'pay_code' => $mockPayCode,
                'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode("TRIPAY:{$mockRef}:{$amount}"),
                'fee' => 4250,
                'amount' => $amount + 4250,
                'instructions' => [],
                'expires_at' => now()->addMinutes(120),
            ];
        }

        return [
            'success' => false,
            'message' => $errMsg ?? 'Gagal menghubungi server Tripay.',
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
     * Fallback standard channel list covering all major payment methods
     */
    public function getDefaultChannels(): array
    {
        return [
            // QRIS & E-Wallet
            [
                'code' => 'QRIS2',
                'name' => 'QRIS (BCA Mobile, GoPay, OVO, ShopeePay, DANA, LinkAja)',
                'group' => 'E-Wallet',
                'fee_customer' => ['flat' => 750, 'percent' => 0.7],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/8ewGzP6SWe1649667701.png',
                'active' => true,
            ],
            [
                'code' => 'OVO',
                'name' => 'OVO',
                'group' => 'E-Wallet',
                'fee_customer' => ['flat' => 0, 'percent' => 3.0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/fH6Y7wDT171586199243.png',
                'active' => true,
            ],
            [
                'code' => 'DANA',
                'name' => 'DANA',
                'group' => 'E-Wallet',
                'fee_customer' => ['flat' => 0, 'percent' => 3.0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/sj3UHLu8Tu1655719621.png',
                'active' => true,
            ],
            [
                'code' => 'SHOPEEPAY',
                'name' => 'ShopeePay',
                'group' => 'E-Wallet',
                'fee_customer' => ['flat' => 0, 'percent' => 3.0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/d204uajhlS1655719774.png',
                'active' => true,
            ],

            // Virtual Accounts
            [
                'code' => 'BCAVA',
                'name' => 'BCA Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 5500, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/ytBKvaleGy1605201833.png',
                'active' => true,
            ],
            [
                'code' => 'BRIVA',
                'name' => 'BRI Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/8WQ3APST5s1579461828.png',
                'active' => true,
            ],
            [
                'code' => 'MANDIRIVA',
                'name' => 'Mandiri Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/T9Z012UE331583531536.png',
                'active' => true,
            ],
            [
                'code' => 'BNIVA',
                'name' => 'BNI Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/n22Qsh8jMa1583433577.png',
                'active' => true,
            ],
            [
                'code' => 'PERMATAVA',
                'name' => 'Permata Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/szezRhAALB1583408731.png',
                'active' => true,
            ],
            [
                'code' => 'CIMBVA',
                'name' => 'CIMB Niaga Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/WtEJwfuphn1614003973.png',
                'active' => true,
            ],
            [
                'code' => 'BSIVA',
                'name' => 'BSI (Bank Syariah Indonesia) Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/tEclz5Assb1643375216.png',
                'active' => true,
            ],
            [
                'code' => 'MUAMALATVA',
                'name' => 'Muamalat Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/GGwwcgdYaG1611929720.png',
                'active' => true,
            ],
            [
                'code' => 'OCBCVA',
                'name' => 'OCBC NISP Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/ysiSToLvKl1644244798.png',
                'active' => true,
            ],
            [
                'code' => 'DANAMONVA',
                'name' => 'Danamon Virtual Account',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/F3pGzDOLUz1644245546.png',
                'active' => true,
            ],
            [
                'code' => 'OTHERBANKVA',
                'name' => 'Bank Lainnya (ATM Bersama / Prima / Alto)',
                'group' => 'Virtual Account',
                'fee_customer' => ['flat' => 4250, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/qQYo61sIDa1702995837.png',
                'active' => true,
            ],

            // Convenience Stores
            [
                'code' => 'ALFAMART',
                'name' => 'Alfamart / Alfamidi',
                'group' => 'Convenience Store',
                'fee_customer' => ['flat' => 3500, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/zN2Nbg7m2u1583434056.png',
                'active' => false,
            ],
            [
                'code' => 'INDOMARET',
                'name' => 'Indomaret',
                'group' => 'Convenience Store',
                'fee_customer' => ['flat' => 3500, 'percent' => 0],
                'icon_url' => 'https://assets.tripay.co.id/upload/payment-icon/Indomaret.png',
                'active' => false,
            ],
        ];
    }
}
