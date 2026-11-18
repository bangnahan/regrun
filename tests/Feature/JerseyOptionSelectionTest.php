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

class JerseyOptionSelectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_registration_with_long_sleeve_adds_surcharge_correctly(): void
    {
        $event = Event::first();
        $category = TicketCategory::where('event_id', $event->id)->first();
        $sizeL = JerseySize::where('size_code', 'L')->first();
        $sizeM = JerseySize::where('size_code', 'M')->first();

        // Step 1: Select 2 tickets
        $this->post(route('register.step_participants'), [
            'event_id' => $event->id,
            'tickets' => [
                $category->id => 2,
            ],
        ]);

        // Step 2: Fill participant details: 1 Long Sleeve (+20.000) and 1 Singlet (0)
        $participantsData = [
            [
                'full_name' => 'Pelari Lengan Panjang',
                'identity_number' => '3201010101010001',
                'gender' => 'L',
                'date_of_birth' => '1992-04-10',
                'phone_number' => '081234567801',
                'email' => 'panjang@example.com',
                'jersey_size_id' => $sizeL->id,
                'jersey_type' => 'long',
                'blood_type' => 'O',
                'bib_name' => 'PANJANG',
                'emergency_contact_name' => 'Kontak 1',
                'emergency_contact_phone' => '081299990001',
                'emergency_contact_relation' => 'Kerabat',
            ],
            [
                'full_name' => 'Pelari Singlet',
                'identity_number' => '3201010101010002',
                'gender' => 'P',
                'date_of_birth' => '1996-09-15',
                'phone_number' => '081234567802',
                'email' => 'singlet@example.com',
                'jersey_size_id' => $sizeM->id,
                'jersey_type' => 'singlet',
                'blood_type' => 'B',
                'bib_name' => 'SINGLET',
                'emergency_contact_name' => 'Kontak 2',
                'emergency_contact_phone' => '081299990002',
                'emergency_contact_relation' => 'Teman',
            ],
        ];

        $checkoutResponse = $this->post(route('register.step_checkout'), [
            'participants' => $participantsData,
        ]);

        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('Tambahan Lengan Panjang');
        $checkoutResponse->assertSee('+Rp 20.000');

        // Step 3: Process payment
        $payResponse = $this->post(route('register.process_payment'), [
            'buyer_name' => 'Pembeli Jersey Test',
            'buyer_email' => 'buyer_jersey@example.com',
            'buyer_phone' => '08111222999',
            'payment_method' => 'QRIS',
            'agree_terms' => '1',
        ]);

        $expectedSubtotal = ($category->current_price * 2) + 20000;

        $this->assertDatabaseHas('transactions', [
            'buyer_email' => 'buyer_jersey@example.com',
            'subtotal' => $expectedSubtotal,
            'status' => 'UNPAID',
        ]);

        $this->assertDatabaseHas('participants', [
            'full_name' => 'Pelari Lengan Panjang',
            'jersey_type' => 'long',
            'jersey_surcharge' => 20000.00,
        ]);

        $this->assertDatabaseHas('participants', [
            'full_name' => 'Pelari Singlet',
            'jersey_type' => 'singlet',
            'jersey_surcharge' => 0.00,
        ]);

        $pPanjang = Participant::where('full_name', 'Pelari Lengan Panjang')->first();
        $this->assertSame('Lengan Panjang', $pPanjang->jersey_type_label);

        $pSinglet = Participant::where('full_name', 'Pelari Singlet')->first();
        $this->assertSame('Singlet', $pSinglet->jersey_type_label);
    }

    public function test_admin_jersey_recap_and_export_handles_jersey_types(): void
    {
        $admin = User::first();
        $event = Event::first();
        $category = TicketCategory::where('event_id', $event->id)->first();
        $sizeL = JerseySize::where('size_code', 'L')->first();

        // Create transaction and participant with long sleeve
        $trx = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-TEST-LONG-1',
            'tripay_merchant_ref' => 'REF-LONG-1',
            'buyer_name' => 'Admin Test',
            'buyer_email' => 'admintest@example.com',
            'buyer_phone' => '0812345678',
            'subtotal' => $category->price + 20000,
            'fee_amount' => 4500,
            'grand_total' => $category->price + 24500,
            'payment_method' => 'QRIS',
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        Participant::create([
            'transaction_id' => $trx->id,
            'ticket_category_id' => $category->id,
            'jersey_size_id' => $sizeL->id,
            'jersey_type' => 'long',
            'jersey_surcharge' => 20000,
            'ticket_code' => 'TKT-TEST-001',
            'qr_code_hash' => hash('sha256', 'TKT-TEST-001'),
            'bib_number' => 'BIB-001',
            'full_name' => 'Runner With Long Sleeve',
            'identity_number' => '1234567890123456',
            'gender' => 'L',
            'date_of_birth' => '1990-01-01',
            'phone_number' => '08123456789',
            'email' => 'longrunner@example.com',
            'blood_type' => 'O',
            'bib_name' => 'RUNNER',
            'emergency_contact_name' => 'Emergency',
            'emergency_contact_phone' => '08123456789',
            'emergency_contact_relation' => 'Family',
        ]);

        // Test Recap Page View
        $response = $this->actingAs($admin)->get(route('admin.jersey_recap', ['event_id' => $event->id]));
        $response->assertStatus(200);
        $response->assertSee('Lengan Panjang');
        $response->assertSee('Lengan Pendek');
        $response->assertSee('Singlet');

        // Test Filter by long sleeve
        $responseFiltered = $this->actingAs($admin)->get(route('admin.jersey_recap', [
            'event_id' => $event->id,
            'jersey_type' => 'long',
        ]));
        $responseFiltered->assertStatus(200);

        // Test CSV Export
        $exportResponse = $this->actingAs($admin)->get(route('admin.jersey_recap.export', [
            'event_id' => $event->id,
        ]));
        $exportResponse->assertStatus(200);
        $exportResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');

        // Test Admin Participants List View
        $participantsResponse = $this->actingAs($admin)->get(route('admin.participants', [
            'jersey_type' => 'long',
        ]));
        $participantsResponse->assertStatus(200);
        $participantsResponse->assertSee('Runner With Long Sleeve');
        $participantsResponse->assertSee('Lengan Panjang');
    }

    public function test_admin_can_update_participant_jersey_size_and_sleeve_type(): void
    {
        $admin = User::first();
        $event = Event::first();
        $category = TicketCategory::where('event_id', $event->id)->first();
        $sizeXXL = JerseySize::where('size_code', 'XXL')->first();
        $sizeL = JerseySize::where('size_code', 'L')->first();

        $trx = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-TEST-SWAP-1',
            'tripay_merchant_ref' => 'REF-SWAP-1',
            'buyer_name' => 'Peserta Mau Tukar',
            'buyer_email' => 'tukar@example.com',
            'buyer_phone' => '0812345678',
            'subtotal' => $category->price,
            'fee_amount' => 4500,
            'grand_total' => $category->price + 4500,
            'payment_method' => 'QRIS',
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        $participant = Participant::create([
            'transaction_id' => $trx->id,
            'ticket_category_id' => $category->id,
            'jersey_size_id' => $sizeXXL->id,
            'jersey_type' => 'short',
            'jersey_surcharge' => 0,
            'ticket_code' => 'TKT-SWAP-001',
            'qr_code_hash' => hash('sha256', 'TKT-SWAP-001'),
            'bib_number' => 'BIB-SWAP',
            'full_name' => 'Runner Tukar Size',
            'identity_number' => '1234567890123456',
            'gender' => 'L',
            'date_of_birth' => '1990-01-01',
            'phone_number' => '08123456789',
            'email' => 'tukar@example.com',
            'blood_type' => 'O',
            'bib_name' => 'SWAPPER',
            'emergency_contact_name' => 'Emergency',
            'emergency_contact_phone' => '08123456789',
            'emergency_contact_relation' => 'Family',
        ]);

        // Swap from XXL Short to L Long (+20.000)
        $response = $this->actingAs($admin)->post(route('admin.participants.update_jersey', ['id' => $participant->id]), [
            'jersey_size_id' => $sizeL->id,
            'jersey_type' => 'long',
            'bib_name' => 'SWAPPER L',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $participant->refresh();
        $this->assertSame($sizeL->id, $participant->jersey_size_id);
        $this->assertSame('long', $participant->jersey_type);
        $this->assertEquals(20000.00, $participant->jersey_surcharge);
        $this->assertSame('SWAPPER L', $participant->bib_name);
        $this->assertSame('Lengan Panjang', $participant->jersey_type_label);

        // Verify that Admin Participant list displays the updated info and edit button
        $listView = $this->actingAs($admin)->get(route('admin.participants', ['search' => 'Runner Tukar Size']));
        $listView->assertStatus(200);
        $listView->assertSee('Size L', false);
        $listView->assertSee('Lengan Panjang', false);
        $listView->assertSee('Ubah', false);
    }
}
