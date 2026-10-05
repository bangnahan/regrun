<?php

namespace Tests\Feature;

use App\Models\EmailLog;
use App\Models\Event;
use App\Models\JerseySize;
use App\Models\Participant;
use App\Models\TicketCategory;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MailketingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailketingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_mailketing_service_sends_all_email_types_and_logs()
    {
        $event = Event::first();
        $category = TicketCategory::first();
        $jersey = JerseySize::first();

        $transaction = Transaction::create([
            'event_id' => $event->id,
            'invoice_number' => 'INV-TEST-MK01',
            'tripay_merchant_ref' => 'TRX-TEST-MK01',
            'buyer_name' => 'Budi Santoso',
            'buyer_email' => 'budi@santoso.com',
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
            'ticket_code' => 'TCK-MK01',
            'full_name' => 'Budi Santoso',
            'identity_number' => '3201010101900001',
            'gender' => 'L',
            'date_of_birth' => '1990-01-01',
            'phone_number' => '08123456789',
            'email' => 'budi@santoso.com',
            'blood_type' => 'O',
            'bib_name' => 'BUDI',
            'emergency_contact_name' => 'Siti',
            'emergency_contact_phone' => '081299998888',
            'emergency_contact_relation' => 'Istri',
            'qr_code_hash' => hash('sha256', 'TCK-MK01-SECRET'),
        ]);

        $service = app(MailketingService::class);

        // 1. Pending payment email
        $resPending = $service->sendPendingPaymentEmail($transaction);
        $this->assertTrue($resPending);
        $this->assertDatabaseHas('email_logs', [
            'transaction_id' => $transaction->id,
            'recipient_email' => 'budi@santoso.com',
            'email_type' => 'pending_payment',
            'status' => 'sent',
        ]);

        // 2. Invoice paid email
        $resInvoice = $service->sendInvoiceEmail($transaction);
        $this->assertTrue($resInvoice);
        $this->assertDatabaseHas('email_logs', [
            'transaction_id' => $transaction->id,
            'recipient_email' => 'budi@santoso.com',
            'email_type' => 'invoice',
            'status' => 'sent',
        ]);

        // 3. E-Ticket email
        $resTicket = $service->sendTicketEmail($participant);
        $this->assertTrue($resTicket);
        $this->assertDatabaseHas('email_logs', [
            'participant_id' => $participant->id,
            'recipient_email' => 'budi@santoso.com',
            'email_type' => 'eticket',
            'status' => 'sent',
        ]);
    }

    public function test_admin_settings_displays_mailketing_status_and_sender()
    {
        $admin = User::first();

        $response = $this->actingAs($admin)->get(route('admin.settings'));

        $response->assertStatus(200);
        $response->assertViewHas('mailketingStatus');
        $response->assertSee('Mailketing Email API');
        $response->assertSee('hi@jelatix.com');
        $response->assertSee('Tes Kirim Email');
    }

    public function test_admin_can_send_test_email()
    {
        $admin = User::first();

        $response = $this->actingAs($admin)->post(route('admin.settings.test_email'), [
            'recipient' => 'testadmin@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
