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

class BibGenerationModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_delayed_bib_mode_leaves_bib_number_null_upon_payment()
    {
        $event = Event::first();
        $event->update(['auto_generate_bib' => false]);

        $category = TicketCategory::first();
        $jersey = JerseySize::first();

        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-TEST-DELAYED-BIB',
            'tripay_merchant_ref' => 'TRX-TEST-DELAYED-BIB',
            'buyer_name' => 'Runner Test',
            'buyer_email' => 'runner@test.com',
            'buyer_phone' => '08123456789',
            'subtotal' => 150000,
            'fee_amount' => 4250,
            'grand_total' => 154250,
            'payment_method' => 'BCAVA',
            'status' => 'UNPAID',
        ]);

        $participant = Participant::create([
            'transaction_id' => $transaction->id,
            'ticket_category_id' => $category->id,
            'jersey_size_id' => $jersey->id,
            'ticket_code' => 'TKT-DELAY01',
            'full_name' => 'Runner Test',
            'identity_number' => '3201010101900001',
            'gender' => 'L',
            'date_of_birth' => '1990-01-01',
            'phone_number' => '08123456789',
            'email' => 'runner@test.com',
            'blood_type' => 'O',
            'bib_name' => 'RUNNER01',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_phone' => '081299998888',
            'emergency_contact_relation' => 'Family',
            'qr_code_hash' => 'TKT-DELAY01',
        ]);

        // Simulate payment completion
        $response = $this->post(route('order.simulate_pay', ['invoice' => $transaction->invoice_number]));
        $response->assertRedirect();

        $participant->refresh();
        $this->assertNull($participant->bib_number, 'BIB number should remain null when auto_generate_bib is disabled.');
    }

    public function test_admin_can_bulk_generate_bib_numbers()
    {
        $admin = User::first();
        $event = Event::first();
        $category = TicketCategory::first();
        $jersey = JerseySize::first();

        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-TEST-BULK-BIB',
            'tripay_merchant_ref' => 'TRX-TEST-BULK-BIB',
            'buyer_name' => 'Runner Bulk',
            'buyer_email' => 'bulk@test.com',
            'buyer_phone' => '08123456789',
            'subtotal' => 150000,
            'fee_amount' => 4250,
            'grand_total' => 154250,
            'payment_method' => 'BCAVA',
            'status' => 'PAID',
        ]);

        $participant = Participant::create([
            'transaction_id' => $transaction->id,
            'ticket_category_id' => $category->id,
            'jersey_size_id' => $jersey->id,
            'ticket_code' => 'TKT-BULK01',
            'full_name' => 'Runner Bulk',
            'identity_number' => '3201010101900002',
            'gender' => 'P',
            'date_of_birth' => '1995-01-01',
            'phone_number' => '08123456780',
            'email' => 'bulk@test.com',
            'blood_type' => 'A',
            'bib_name' => 'BULK01',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_phone' => '081299998880',
            'emergency_contact_relation' => 'Family',
            'qr_code_hash' => 'TKT-BULK01',
            'bib_number' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.participants.generate_bibs'), [
            'event_id' => $event->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $participant->refresh();
        $this->assertNotNull($participant->bib_number);
    }

    public function test_admin_can_update_individual_participant_bib()
    {
        $admin = User::first();
        $event = Event::first();
        $category = TicketCategory::first();
        $jersey = JerseySize::first();

        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-TEST-INDIV-BIB',
            'tripay_merchant_ref' => 'TRX-TEST-INDIV-BIB',
            'buyer_name' => 'Runner VIP',
            'buyer_email' => 'vip@test.com',
            'buyer_phone' => '08123456789',
            'subtotal' => 150000,
            'fee_amount' => 4250,
            'grand_total' => 154250,
            'payment_method' => 'BCAVA',
            'status' => 'PAID',
        ]);

        $participant = Participant::create([
            'transaction_id' => $transaction->id,
            'ticket_category_id' => $category->id,
            'jersey_size_id' => $jersey->id,
            'ticket_code' => 'TKT-VIP01',
            'full_name' => 'Runner VIP',
            'identity_number' => '3201010101900003',
            'gender' => 'L',
            'date_of_birth' => '1992-01-01',
            'phone_number' => '08123456788',
            'email' => 'vip@test.com',
            'blood_type' => 'B',
            'bib_name' => 'VIP01',
            'emergency_contact_name' => 'Contact',
            'emergency_contact_phone' => '081299998888',
            'emergency_contact_relation' => 'Family',
            'qr_code_hash' => 'TKT-VIP01',
            'bib_number' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.participants.update_bib', ['id' => $participant->id]), [
            'bib_number' => 'VIP-999',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $participant->refresh();
        $this->assertEquals('VIP-999', $participant->bib_number);
    }

    public function test_admin_can_view_participants_page_with_all_variables()
    {
        $admin = User::first();

        $response = $this->actingAs($admin)->get(route('admin.participants'));

        $response->assertStatus(200);
        $response->assertViewHas('participants');
        $response->assertViewHas('events');
        $response->assertViewHas('categories');
        $response->assertViewHas('jerseySizes');
        $response->assertViewHas('unassignedBibCount');
    }
}
