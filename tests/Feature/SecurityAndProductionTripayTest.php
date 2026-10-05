<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\JerseySize;
use App\Models\SystemSetting;
use App\Models\TicketCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TripayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SecurityAndProductionTripayTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Security Test 1: simulate-pay route MUST return 403 Forbidden in Production mode
     */
    public function test_simulation_route_is_strictly_blocked_in_production_mode(): void
    {
        SystemSetting::set('tripay_mode', 'production', 'tripay');
        SystemSetting::set('tripay_sandbox', '0', 'tripay');

        $event = Event::first();
        $category = TicketCategory::where('event_id', $event->id)->first();
        $jersey = JerseySize::first();

        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-SECURITY-PROD-01',
            'tripay_merchant_ref' => 'TRX-SEC-01',
            'buyer_name' => 'Attacker User',
            'buyer_email' => 'attacker@test.com',
            'buyer_phone' => '08123456789',
            'subtotal' => 175000,
            'fee_amount' => 4500,
            'grand_total' => 179500,
            'payment_method' => 'QRIS',
            'payment_channel_code' => 'QRIS',
            'status' => 'UNPAID',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->post(route('order.simulate_pay', ['invoice' => $transaction->invoice_number]));

        $response->assertStatus(403);
        $this->assertEquals('UNPAID', $transaction->fresh()->status);
    }

    /**
     * Security Test 2: simulate-pay route MUST return 403 Forbidden if app environment is production
     */
    public function test_simulation_route_is_strictly_blocked_in_production_environment(): void
    {
        SystemSetting::set('tripay_mode', 'sandbox', 'tripay');
        SystemSetting::set('tripay_sandbox', '1', 'tripay');

        // Simulate Laravel running in production environment
        $this->app['env'] = 'production';

        $event = Event::first();
        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-SECURITY-ENV-01',
            'tripay_merchant_ref' => 'TRX-SEC-02',
            'buyer_name' => 'Test User',
            'buyer_email' => 'test@test.com',
            'buyer_phone' => '08123456789',
            'subtotal' => 175000,
            'fee_amount' => 4500,
            'grand_total' => 179500,
            'payment_method' => 'QRIS',
            'payment_channel_code' => 'QRIS',
            'status' => 'UNPAID',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class)
            ->post(route('order.simulate_pay', ['invoice' => $transaction->invoice_number]));

        $response->assertStatus(403);
        $this->assertEquals('UNPAID', $transaction->fresh()->status);
    }

    /**
     * Security Test 3: order.show page hides the simulation button when in Production mode
     */
    public function test_order_show_view_hides_simulate_button_in_production(): void
    {
        SystemSetting::set('tripay_mode', 'production', 'tripay');
        SystemSetting::set('tripay_sandbox', '0', 'tripay');

        $event = Event::first();
        $category = TicketCategory::where('event_id', $event->id)->first();
        $jersey = JerseySize::first();

        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-ORDER-VIEW-01',
            'tripay_merchant_ref' => 'TRX-VIEW-01',
            'buyer_name' => 'Customer User',
            'buyer_email' => 'cust@test.com',
            'buyer_phone' => '08123456789',
            'subtotal' => 175000,
            'fee_amount' => 4500,
            'grand_total' => 179500,
            'payment_method' => 'QRIS',
            'payment_channel_code' => 'QRIS',
            'status' => 'UNPAID',
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->get(route('order.show', ['invoice' => $transaction->invoice_number]));

        $response->assertStatus(200);
        $response->assertDontSee('Mode Pengujian Sandbox');
        $response->assertDontSee('Simulasikan Bayar Lunas');
    }

    /**
     * Schema Test 4: TripayService correctly switches Base URL and credentials between Sandbox and Production
     */
    public function test_tripay_service_schema_separation_and_credential_isolation(): void
    {
        // Configure distinct credentials for Sandbox and Production
        SystemSetting::set('tripay_sandbox_merchant_code', 'T_SANDBOX_123', 'tripay');
        SystemSetting::set('tripay_sandbox_api_key', 'DEV-SANDBOX-KEY-XYZ', 'tripay');
        SystemSetting::set('tripay_sandbox_private_key', 'PRIV-SANDBOX-SECRET', 'tripay');

        SystemSetting::set('tripay_prod_merchant_code', 'T_PRODUCTION_999', 'tripay');
        SystemSetting::set('tripay_prod_api_key', 'LIVE-PRODUCTION-KEY-REAL', 'tripay');
        SystemSetting::set('tripay_prod_private_key', 'PRIV-PRODUCTION-SECRET-REAL', 'tripay');

        // Test 1: Sandbox Mode
        SystemSetting::set('tripay_mode', 'sandbox', 'tripay');
        $sandboxService = new TripayService();

        $this->assertTrue($sandboxService->isSandbox());
        $this->assertEquals('sandbox', $sandboxService->getMode());
        $this->assertEquals('https://tripay.co.id/api-sandbox/', $sandboxService->getBaseUrl());
        $this->assertEquals('T_SANDBOX_123', $sandboxService->getMerchantCode());
        $this->assertEquals('DEV-SANDBOX-KEY-XYZ', $sandboxService->getApiKey());
        $this->assertEquals('PRIV-SANDBOX-SECRET', $sandboxService->getPrivateKey());

        // Test 2: Production Mode
        SystemSetting::set('tripay_mode', 'production', 'tripay');
        $productionService = new TripayService();

        $this->assertFalse($productionService->isSandbox());
        $this->assertEquals('production', $productionService->getMode());
        $this->assertEquals('https://tripay.co.id/api/', $productionService->getBaseUrl());
        $this->assertEquals('T_PRODUCTION_999', $productionService->getMerchantCode());
        $this->assertEquals('LIVE-PRODUCTION-KEY-REAL', $productionService->getApiKey());
        $this->assertEquals('PRIV-PRODUCTION-SECRET-REAL', $productionService->getPrivateKey());
    }

    /**
     * Schema Test 5: QRIS channel mapping differs between Sandbox and Production
     */
    public function test_qris_channel_code_differs_between_sandbox_and_production(): void
    {
        // In Sandbox: default channels provide QRIS2
        SystemSetting::set('tripay_mode', 'sandbox', 'tripay');
        $sandboxService = new TripayService();
        $sandboxChannels = $sandboxService->getDefaultChannels();
        $sandboxQris = collect($sandboxChannels)->firstWhere('group', 'E-Wallet');
        $this->assertEquals('QRIS2', $sandboxQris['code']);
        $this->assertStringContainsString('Sandbox', $sandboxQris['name']);

        // In Production: default channels provide QRIS
        SystemSetting::set('tripay_mode', 'production', 'tripay');
        $prodService = new TripayService();
        $prodChannels = $prodService->getDefaultChannels();
        $prodQris = collect($prodChannels)->firstWhere('group', 'E-Wallet');
        $this->assertEquals('QRIS', $prodQris['code']);
        $this->assertStringContainsString('Nasional', $prodQris['name']);
    }

    /**
     * Security Test 6: Webhook callback signature verification & tampering defense
     */
    public function test_webhook_rejects_missing_or_invalid_signature(): void
    {
        $event = Event::first();
        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-WEBHOOK-SEC-01',
            'tripay_merchant_ref' => 'TRX-WH-01',
            'buyer_name' => 'Target Runner',
            'buyer_email' => 'target@test.com',
            'buyer_phone' => '08123456789',
            'subtotal' => 175000,
            'fee_amount' => 4500,
            'grand_total' => 179500,
            'payment_method' => 'QRIS',
            'payment_channel_code' => 'QRIS',
            'status' => 'UNPAID',
            'expires_at' => now()->addHour(),
        ]);

        $payload = json_encode([
            'reference' => 'DEV-T123456',
            'merchant_ref' => $transaction->invoice_number,
            'status' => 'PAID',
            'total_amount' => 179500,
        ]);

        // Attempt 1: Without signature header
        $responseNoSig = $this->call(
            'POST',
            route('tripay.callback'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $payload
        );
        $responseNoSig->assertStatus(403);
        $this->assertEquals('UNPAID', $transaction->fresh()->status);

        // Attempt 2: With forged/fake signature
        $responseFakeSig = $this->call(
            'POST',
            route('tripay.callback'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CALLBACK_SIGNATURE' => 'forged_fake_signature_hash',
                'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            ],
            $payload
        );
        $responseFakeSig->assertStatus(403);
        $this->assertEquals('UNPAID', $transaction->fresh()->status);

        // Attempt 3: With valid HMAC-SHA256 signature calculated from current private key
        $service = app(TripayService::class);
        $validSignature = hash_hmac('sha256', $payload, $service->getPrivateKey());

        $responseValid = $this->call(
            'POST',
            route('tripay.callback'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CALLBACK_SIGNATURE' => $validSignature,
                'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            ],
            $payload
        );
        $responseValid->assertStatus(200);
        $this->assertEquals('PAID', $transaction->fresh()->status);
    }

    /**
     * Security Test 7: Webhook EXPIRED releases reserved ticket quota
     */
    public function test_webhook_expired_releases_reserved_ticket_quota(): void
    {
        $event = Event::first();
        $category = TicketCategory::where('event_id', $event->id)->first();
        $category->update(['reserved_count' => 2]);

        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-EXPIRED-TEST-01',
            'tripay_merchant_ref' => 'TRX-EXP-01',
            'buyer_name' => 'Expired Runner',
            'buyer_email' => 'exp@test.com',
            'buyer_phone' => '08123456789',
            'subtotal' => 350000,
            'fee_amount' => 4500,
            'grand_total' => 354500,
            'payment_method' => 'QRIS',
            'payment_channel_code' => 'QRIS',
            'status' => 'UNPAID',
            'expires_at' => now()->subHour(),
        ]);

        $transaction->items()->create([
            'ticket_category_id' => $category->id,
            'quantity' => 2,
            'unit_price' => 175000,
            'subtotal' => 350000,
        ]);

        $payload = json_encode([
            'reference' => 'DEV-T999999',
            'merchant_ref' => $transaction->invoice_number,
            'status' => 'EXPIRED',
        ]);

        $service = app(TripayService::class);
        $validSignature = hash_hmac('sha256', $payload, $service->getPrivateKey());

        $response = $this->call(
            'POST',
            route('tripay.callback'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CALLBACK_SIGNATURE' => $validSignature,
            ],
            $payload
        );

        $response->assertStatus(200);
        $this->assertEquals('EXPIRED', $transaction->fresh()->status);
        $this->assertEquals(0, $category->fresh()->reserved_count);
    }

    /**
     * Admin Test 8: Admin can save separate Sandbox and Production credentials without collision
     */
    public function test_admin_settings_saves_both_sandbox_and_production_credentials(): void
    {
        $admin = User::first();

        $payload = [
            'tripay_mode' => 'production',
            'tripay_sandbox_merchant_code' => 'T_SANDBOX_SAVED',
            'tripay_sandbox_api_key' => 'DEV_SANDBOX_KEY_SAVED',
            'tripay_sandbox_private_key' => 'PRIV_SANDBOX_SAVED',
            'tripay_prod_merchant_code' => 'T_PROD_SAVED',
            'tripay_prod_api_key' => 'LIVE_PROD_KEY_SAVED',
            'tripay_prod_private_key' => 'PRIV_PROD_SAVED',
            'mailketing_api_token' => 'token123',
            'mailketing_sender_email' => 'admin@regrun.test',
            'mailketing_sender_name' => 'Panitia RegRun',
            'auto_generate_bib' => '1',
        ];

        $response = $this->actingAs($admin)->post(route('admin.settings.save'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('production', SystemSetting::get('tripay_mode'));
        $this->assertEquals('0', SystemSetting::get('tripay_sandbox'));
        $this->assertEquals('T_SANDBOX_SAVED', SystemSetting::get('tripay_sandbox_merchant_code'));
        $this->assertEquals('DEV_SANDBOX_KEY_SAVED', SystemSetting::get('tripay_sandbox_api_key'));
        $this->assertEquals('T_PROD_SAVED', SystemSetting::get('tripay_prod_merchant_code'));
        $this->assertEquals('LIVE_PROD_KEY_SAVED', SystemSetting::get('tripay_prod_api_key'));
    }
}
