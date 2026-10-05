<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\JerseySize;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TripayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripayIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_tripay_service_connection_and_channels()
    {
        $service = app(TripayService::class);
        $status = $service->testConnection();

        $this->assertTrue($status['connected']);
        $this->assertEquals('T39430', $status['merchant_code']);
        $this->assertGreaterThanOrEqual(10, $status['channel_count']);

        $channels = $service->getPaymentChannels();
        $this->assertNotEmpty($channels);

        $codes = collect($channels)->pluck('code')->toArray();
        $this->assertContains('BRIVA', $codes);
        $this->assertContains('BCAVA', $codes);
    }

    public function test_step3_checkout_displays_all_tripay_channels_dropdown()
    {
        $event = Event::first();
        $category = TicketCategory::where('event_id', $event->id)->first();
        $jersey = JerseySize::first();

        // Simulate session from steps 1 & 2
        $sessionData = [
            'registration_event_id' => $event->id,
            'registration_tickets' => [
                [
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_code' => $category->code,
                    'quantity' => 1,
                    'price' => $category->current_price,
                ]
            ],
        ];

        $participantPayload = [
            'participants' => [
                [
                    'full_name' => 'Budi Runner',
                    'identity_number' => '3171010101900001',
                    'gender' => 'L',
                    'date_of_birth' => '1990-05-15',
                    'phone_number' => '081234567890',
                    'email' => 'budi@runner.com',
                    'jersey_size_id' => $jersey->id,
                    'blood_type' => 'O',
                    'bib_name' => 'BUDI R',
                    'emergency_contact_name' => 'Siti',
                    'emergency_contact_phone' => '081299998888',
                    'emergency_contact_relation' => 'Istri',
                ]
            ]
        ];

        $response = $this->withSession($sessionData)
            ->post(route('register.step_checkout'), $participantPayload);

        $response->assertStatus(200);
        $response->assertViewHas('paymentChannels');

        // Check that channels are present in HTML dropdown
        $response->assertSee('BRIVA');
        $response->assertSee('BCAVA');
        $response->assertSee('QRIS');
        $response->assertSee('Lanjut Pembayaran Tripay');
    }

    public function test_admin_settings_displays_tripay_live_status()
    {
        $admin = User::first();

        $response = $this->actingAs($admin)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertViewHas('tripayStatus');
        $response->assertSee('Status Koneksi API Tripay');
        $response->assertSee('TERKONEKSI');
        $response->assertSee('T39430');
    }
}
