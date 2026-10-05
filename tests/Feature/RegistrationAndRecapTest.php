<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\JerseySize;
use App\Models\Participant;
use App\Models\TicketCategory;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAndRecapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_view_registration_page_with_dummy_event()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Nusantara Sunset Run 2026');
        $response->assertSee('5K Fun Run');
        $response->assertSee('10K Open Category');
        $response->assertSee('21K Half Marathon');
    }

    public function test_step_1_to_step_2_submission()
    {
        $event = Event::first();
        $category = TicketCategory::first();

        $response = $this->post(route('register.step_participants'), [
            'event_id' => $event->id,
            'tickets' => [
                $category->id => 2,
            ],
        ]);

        $response->assertStatus(200);
        $response->assertSee('Peserta 1');
        $response->assertSee('Peserta 2');
        $response->assertSee('Ukuran Jersey Event');
    }

    public function test_full_checkout_process_and_jersey_recap_matrix()
    {
        $event = Event::first();
        $category5K = TicketCategory::where('code', '5K')->first();
        $sizeL = JerseySize::where('size_code', 'L')->first();
        $size5XL = JerseySize::where('size_code', '5XL')->first();

        // 1. Step 1: Select tickets
        $this->post(route('register.step_participants'), [
            'event_id' => $event->id,
            'tickets' => [
                $category5K->id => 2,
            ],
        ]);

        // 2. Step 2: Fill Participants
        $participantsData = [
            [
                'full_name' => 'Pelari Satu',
                'identity_number' => '3171010101010001',
                'gender' => 'L',
                'date_of_birth' => '1995-05-15',
                'phone_number' => '081234567890',
                'email' => 'pelari1@example.com',
                'jersey_size_id' => $sizeL->id,
                'blood_type' => 'O',
                'bib_name' => 'PELARI 1',
                'emergency_contact_name' => 'Ibu Pelari',
                'emergency_contact_phone' => '081299999999',
                'emergency_contact_relation' => 'Orang Tua',
                'running_club' => 'Jakarta Runners',
            ],
            [
                'full_name' => 'Pelari Dua',
                'identity_number' => '3171010101010002',
                'gender' => 'P',
                'date_of_birth' => '1998-08-20',
                'phone_number' => '081234567891',
                'email' => 'pelari2@example.com',
                'jersey_size_id' => $size5XL->id,
                'blood_type' => 'A',
                'bib_name' => 'PELARI 2',
                'emergency_contact_name' => 'Ayah Pelari',
                'emergency_contact_phone' => '081288888888',
                'emergency_contact_relation' => 'Orang Tua',
                'running_club' => 'Jakarta Runners',
            ],
        ];

        $checkoutResponse = $this->post(route('register.step_checkout'), [
            'participants' => $participantsData,
        ]);
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('Data Pemesan / Penanggung Jawab');
        $checkoutResponse->assertSee('Metode Pembayaran (Tripay)');

        // 3. Step 3: Process Payment
        $payResponse = $this->post(route('register.process_payment'), [
            'buyer_name' => 'Pembeli Kolektif',
            'buyer_email' => 'pembeli@example.com',
            'buyer_phone' => '08111222333',
            'payment_method' => 'QRIS',
            'agree_terms' => '1',
        ]);

        $this->assertDatabaseHas('transactions', [
            'buyer_email' => 'pembeli@example.com',
            'status' => 'UNPAID',
        ]);

        $trx = Transaction::where('buyer_email', 'pembeli@example.com')->first();
        $this->assertCount(2, $trx->participants);

        // 4. Simulate Payment Success
        $simulateResponse = $this->post(route('order.simulate_pay', ['invoice' => $trx->invoice_number]));
        $simulateResponse->assertRedirect(route('order.show', ['invoice' => $trx->invoice_number]));

        $trx->refresh();
        $this->assertEquals('PAID', $trx->status);

        // 5. Test Admin Jersey Recap
        $admin = User::first();
        $recapResponse = $this->actingAs($admin)->get(route('admin.jersey_recap', ['event_id' => $event->id]));
        $recapResponse->assertStatus(200);
        $recapResponse->assertSee('Matriks Produksi: Kategori Lomba');
        $recapResponse->assertSee('5XL');

        // 6. Test Admin Export CSV
        $exportResponse = $this->actingAs($admin)->get(route('admin.jersey_recap.export', ['event_id' => $event->id]));
        $exportResponse->assertStatus(200);
        $this->assertTrue(str_contains($exportResponse->headers->get('content-type'), 'text/csv'));
    }
}
