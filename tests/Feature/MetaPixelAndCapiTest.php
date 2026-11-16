<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\JerseySize;
use App\Models\TicketCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MetaCapiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaPixelAndCapiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_meta_capi_normalization_and_hashing(): void
    {
        $service = new MetaCapiService;

        // 1. Email normalization (trim, lowercase, sha256)
        $email = '  Runner.Pro@Example.COM ';
        $expectedEmailHash = hash('sha256', 'runner.pro@example.com');
        $this->assertSame($expectedEmailHash, $service->hashEmail($email));

        // 2. Phone normalization (08123... -> 628123...)
        $phone1 = '0812-3456-7890';
        $expectedPhoneHash = hash('sha256', '6281234567890');
        $this->assertSame($expectedPhoneHash, $service->hashPhone($phone1));

        $phone2 = '+62 813-9999-0000';
        $expectedPhoneHash2 = hash('sha256', '6281399990000');
        $this->assertSame($expectedPhoneHash2, $service->hashPhone($phone2));

        // 3. Name normalization
        [$fn, $ln] = $service->hashName('Budi Pratama Santoso');
        $this->assertSame(hash('sha256', 'budi'), $fn);
        $this->assertSame(hash('sha256', 'pratama santoso'), $ln);
    }

    public function test_event_views_render_meta_pixel_code_when_configured(): void
    {
        $event = Event::first();
        $event->update([
            'meta_pixel_id' => '987654321012345',
            'meta_capi_token' => 'EAAG_TEST_TOKEN_XYZ',
            'meta_capi_test_code' => 'TEST12345',
        ]);

        $category = TicketCategory::where('event_id', $event->id)->first();

        // 1. ViewContent on step 1
        $responseStep1 = $this->get(route('register.event', ['slug' => $event->slug]));
        $responseStep1->assertStatus(200);
        $responseStep1->assertSee('987654321012345');
        $responseStep1->assertSee('fbq(\'init\', \'987654321012345\');', false);
        $responseStep1->assertSee('ViewContent');
        $responseStep1->assertSee('AddToCart');

        // Step 1 -> Step 2
        $this->post(route('register.step_participants'), [
            'event_id' => $event->id,
            'tickets' => [$category->id => 1],
        ]);

        // 2. AddPaymentInfo on step 2
        $responseStep2 = $this->get(route('register.step_participants'));
        $responseStep2->assertStatus(200);
        $responseStep2->assertSee('AddPaymentInfo');

        // Step 2 -> Step 3
        $size = JerseySize::first();
        $this->post(route('register.step_checkout'), [
            'participants' => [
                [
                    'full_name' => 'Budi Santoso',
                    'identity_number' => '3201010101010001',
                    'gender' => 'L',
                    'date_of_birth' => '1995-05-15',
                    'phone_number' => '081234567890',
                    'email' => 'budi@example.com',
                    'jersey_size_id' => $size->id,
                    'jersey_type' => 'short',
                    'blood_type' => 'O',
                    'bib_name' => 'BUDI',
                    'emergency_contact_name' => 'Kontak',
                    'emergency_contact_phone' => '081299990001',
                    'emergency_contact_relation' => 'Kerabat',
                ],
            ],
        ]);

        // 3. InitiateCheckout on step 3
        $responseStep3 = $this->get(route('register.step_checkout'));
        $responseStep3->assertStatus(200);
        $responseStep3->assertSee('InitiateCheckout');
        $responseStep3->assertSee('meta_fbp');
        $responseStep3->assertSee('meta_fbc');
    }

    public function test_admin_can_save_meta_pixel_and_capi_settings_per_event(): void
    {
        $admin = User::first();
        $event = Event::first();

        $response = $this->actingAs($admin)->post(route('admin.events.update', ['id' => $event->id]), [
            'title' => $event->title,
            'slug' => $event->slug,
            'race_date' => $event->race_date->format('Y-m-d'),
            'race_start_time' => '06:00:00',
            'venue_name' => $event->venue_name,
            'meta_pixel_id' => '112233445566778',
            'meta_capi_token' => 'EAAG_SAMPLE_TOKEN_SECRET',
            'meta_capi_test_code' => 'TEST9999',
        ]);

        $response->assertSessionHasNoErrors();

        $event->refresh();
        $this->assertSame('112233445566778', $event->meta_pixel_id);
        $this->assertSame('EAAG_SAMPLE_TOKEN_SECRET', $event->meta_capi_token);
        $this->assertSame('TEST9999', $event->meta_capi_test_code);
        $this->assertTrue($event->hasMetaPixel());
        $this->assertTrue($event->hasMetaCapi());
    }

    public function test_capi_purchase_is_triggered_when_tripay_webhook_marks_transaction_paid(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
                'messages' => [],
                'fbtrace_id' => 'trace_12345',
            ], 200),
        ]);

        $event = Event::first();
        $event->update([
            'meta_pixel_id' => '123456789012345',
            'meta_capi_token' => 'TEST_TOKEN_FB_GRAPH',
            'meta_capi_test_code' => 'TEST_CAPI_1',
            'tripay_private_key' => 'test_private_key',
        ]);

        $category = TicketCategory::where('event_id', $event->id)->first();

        $trx = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-CAPI-001',
            'tripay_merchant_ref' => 'REF-CAPI-001',
            'buyer_name' => 'Agus Salim',
            'buyer_email' => 'agus@example.com',
            'buyer_phone' => '081234567890',
            'subtotal' => $category->price,
            'fee_amount' => 4500,
            'grand_total' => $category->price + 4500,
            'payment_method' => 'QRIS',
            'status' => 'UNPAID',
            'ip_address' => '180.252.10.10',
            'user_agent' => 'Mozilla/5.0 (iPhone)',
            'meta_fbp' => 'fb.1.1731760000.123456789',
            'meta_fbc' => 'fb.1.1731760000.IwAR2_SampleClickId',
        ]);

        $payload = [
            'reference' => 'DEV-T123456789',
            'merchant_ref' => 'REF-CAPI-001',
            'payment_method' => 'QRIS',
            'status' => 'PAID',
            'total_amount' => (int) $trx->grand_total,
        ];
        $rawJson = json_encode($payload);
        $signature = hash_hmac('sha256', $rawJson, 'test_private_key');

        $response = $this->withHeaders([
            'X-Callback-Signature' => $signature,
            'X-Callback-Event' => 'payment_status',
            'Content-Type' => 'application/json',
        ])->postJson(route('tripay.callback'), $payload);

        $response->assertStatus(200);

        $trx->refresh();
        $this->assertSame('PAID', $trx->status);
        $this->assertNotNull($trx->capi_purchase_sent_at);

        // Verify Graph API was called
        Http::assertSent(function ($request) use ($trx) {
            $data = $request->data();
            $purchaseEvent = $data['data'][0] ?? [];

            return str_contains($request->url(), '123456789012345/events')
                && ($purchaseEvent['event_name'] ?? '') === 'Purchase'
                && ($purchaseEvent['event_id'] ?? '') === $trx->invoice_number
                && ($purchaseEvent['custom_data']['currency'] ?? '') === 'IDR'
                && ($data['test_event_code'] ?? '') === 'TEST_CAPI_1';
        });
    }

    public function test_capi_purchase_is_triggered_when_admin_manually_marks_as_paid(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'events_received' => 1,
            ], 200),
        ]);

        $admin = User::first();
        $event = Event::first();
        $event->update([
            'meta_pixel_id' => '123456789012345',
            'meta_capi_token' => 'TEST_TOKEN_MANUAL',
        ]);

        $category = TicketCategory::where('event_id', $event->id)->first();

        $trx = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-MANUAL-CAPI-1',
            'tripay_merchant_ref' => 'REF-MANUAL-1',
            'buyer_name' => 'Siti Nurhaliza',
            'buyer_email' => 'siti@example.com',
            'buyer_phone' => '081299998888',
            'subtotal' => $category->price,
            'fee_amount' => 4500,
            'grand_total' => $category->price + 4500,
            'payment_method' => 'BCAVA',
            'status' => 'UNPAID',
        ]);

        $this->actingAs($admin)->post(route('admin.transactions.mark_paid', ['invoice' => $trx->invoice_number]));

        $trx->refresh();
        $this->assertSame('PAID', $trx->status);
        $this->assertNotNull($trx->capi_purchase_sent_at);

        Http::assertSent(function ($request) use ($trx) {
            $data = $request->data();
            $purchaseEvent = $data['data'][0] ?? [];

            return ($purchaseEvent['event_name'] ?? '') === 'Purchase'
                && ($purchaseEvent['event_id'] ?? '') === $trx->invoice_number;
        });
    }

    public function test_order_show_only_fires_browser_purchase_when_paid(): void
    {
        $event = Event::first();
        $event->update([
            'meta_pixel_id' => '123456789012345',
        ]);
        $category = TicketCategory::where('event_id', $event->id)->first();

        // 1. Unpaid order
        $unpaidTrx = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-UNPAID-TEST',
            'tripay_merchant_ref' => 'REF-UNPAID-TEST',
            'buyer_name' => 'Unpaid User',
            'buyer_email' => 'unpaid@example.com',
            'buyer_phone' => '0812000000',
            'subtotal' => $category->price,
            'fee_amount' => 4500,
            'grand_total' => $category->price + 4500,
            'status' => 'UNPAID',
        ]);

        $responseUnpaid = $this->get(route('order.show', ['invoice' => $unpaidTrx->invoice_number]));
        $responseUnpaid->assertStatus(200);
        $responseUnpaid->assertDontSee('fbq(\'track\', \'Purchase\'');

        // 2. Paid order
        $paidTrx = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-PAID-TEST',
            'tripay_merchant_ref' => 'REF-PAID-TEST',
            'buyer_name' => 'Paid User',
            'buyer_email' => 'paid@example.com',
            'buyer_phone' => '0812000001',
            'subtotal' => $category->price,
            'fee_amount' => 4500,
            'grand_total' => $category->price + 4500,
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        $responsePaid = $this->get(route('order.show', ['invoice' => $paidTrx->invoice_number]));
        $responsePaid->assertStatus(200);
        $responsePaid->assertSee('fbq(\'track\', \'Purchase\'', false);
        $responsePaid->assertSee('eventID: \'INV-PAID-TEST\'', false);
    }
}
