<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventDomain;
use App\Models\TicketCategory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Participant;
use App\Models\JerseySize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiDomainDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Event $eventA;
    protected Event $eventB;
    protected TicketCategory $categoryA;
    protected TicketCategory $categoryB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        // 1. Setup Event A (Nusantara Marathon) with Domain A
        $this->eventA = Event::where('is_default', true)->first();
        $this->categoryA = $this->eventA->ticketCategories()->first();

        EventDomain::create([
            'event_id' => $this->eventA->id,
            'domain' => 'tiket.marathon2026.com',
            'is_primary' => true,
            'is_active' => true,
        ]);

        // 2. Setup Event B (Jakarta Night 10K) with Domain B
        $this->eventB = Event::create([
            'title' => 'Jakarta Night 10K',
            'slug' => 'jakarta-night-10k',
            'venue_name' => 'Monas Jakarta',
            'race_date' => now()->addMonths(2),
            'race_start_time' => '19:00:00',
            'is_active' => true,
            'is_default' => false,
            'tripay_merchant_code' => 'T_CUSTOM_EVENT_B',
            'tripay_api_key' => 'KEY_EVENT_B',
            'tripay_private_key' => 'SEC_EVENT_B',
        ]);

        EventDomain::create([
            'event_id' => $this->eventB->id,
            'domain' => 'tiket.jakartanightrun.id',
            'is_primary' => true,
            'is_active' => true,
        ]);

        $this->categoryB = TicketCategory::create([
            'event_id' => $this->eventB->id,
            'name' => '10K Regular Night',
            'code' => '10K-NIGHT',
            'quota' => 200,
            'sold_count' => 0,
            'reserved_count' => 0,
            'normal_price' => 300000,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Test 1: Parallel/Concurrent domain requests resolve their own event contexts without bleeding
     */
    public function test_concurrent_domain_requests_resolve_isolated_contexts(): void
    {
        // Request on Domain A
        $responseA = $this->get('http://tiket.marathon2026.com/');
        $responseA->assertStatus(200);
        $responseA->assertSee($this->eventA->title);
        $responseA->assertDontSee($this->eventB->title);
        $this->assertEquals($this->eventA->id, app('currentEvent')->id);

        // Immediately follow with Request on Domain B (simulating parallel worker execution)
        $responseB = $this->get('http://tiket.jakartanightrun.id/');
        $responseB->assertStatus(200);
        $responseB->assertSee($this->eventB->title);
        $responseB->assertDontSee($this->eventA->title);
        $this->assertEquals($this->eventB->id, app('currentEvent')->id);
    }

    /**
     * Test 2: Category list on Domain A only displays Event A's categories and not Event B's
     */
    public function test_domain_only_renders_its_own_ticket_categories(): void
    {
        $responseA = $this->get('http://tiket.marathon2026.com/');
        $responseA->assertSee($this->categoryA->name);
        $responseA->assertDontSee($this->categoryB->name);

        $responseB = $this->get('http://tiket.jakartanightrun.id/');
        $responseB->assertSee($this->categoryB->name);
        $responseB->assertDontSee($this->categoryA->name);
    }

    /**
     * Test 3: Tenant Parameter Tampering Prevention:
     * Sending event_id of Event B on Domain A must be rejected with 403 Forbidden
     */
    public function test_cross_domain_parameter_tampering_is_blocked(): void
    {
        $response = $this->post('http://tiket.marathon2026.com/register/participants', [
            'event_id' => $this->eventB->id, // Tampered ID!
            'tickets' => [
                $this->categoryB->id => 1,
            ],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test 4: Quota updates on Event A do NOT affect Event B's inventory
     */
    public function test_quota_and_inventory_are_isolated_per_event(): void
    {
        $initialQuotaA = $this->categoryA->remaining_quota;
        $initialQuotaB = $this->categoryB->remaining_quota;

        // Step 1 -> 2 on Domain A
        $this->post('http://tiket.marathon2026.com/register/participants', [
            'event_id' => $this->eventA->id,
            'tickets' => [
                $this->categoryA->id => 2,
            ],
        ])->assertStatus(200);

        // Step 2 -> 3
        $jersey = JerseySize::first();
        $this->post('http://tiket.marathon2026.com/register/checkout', [
            'participants' => [
                [
                    'full_name' => 'Runner Satu',
                    'identity_number' => '3201010101010001',
                    'gender' => 'L',
                    'date_of_birth' => '1995-05-15',
                    'phone_number' => '081234567890',
                    'email' => 'runner1@example.com',
                    'jersey_size_id' => $jersey->id,
                    'blood_type' => 'O',
                    'bib_name' => 'RUNNER1',
                    'emergency_contact_name' => 'Kontak Darurat',
                    'emergency_contact_phone' => '081299999999',
                    'emergency_contact_relation' => 'Saudara',
                ],
                [
                    'full_name' => 'Runner Dua',
                    'identity_number' => '3201010101010002',
                    'gender' => 'P',
                    'date_of_birth' => '1998-08-20',
                    'phone_number' => '081234567891',
                    'email' => 'runner2@example.com',
                    'jersey_size_id' => $jersey->id,
                    'blood_type' => 'A',
                    'bib_name' => 'RUNNER2',
                    'emergency_contact_name' => 'Kontak Darurat',
                    'emergency_contact_phone' => '081299999999',
                    'emergency_contact_relation' => 'Saudara',
                ],
            ],
        ])->assertStatus(200);

        // Step 3 -> 4: Process Payment on Domain A
        $payResponse = $this->post('http://tiket.marathon2026.com/register/pay', [
            'buyer_name' => 'Pembeli Tiket',
            'buyer_email' => 'buyer@example.com',
            'buyer_phone' => '081234567890',
            'payment_method' => 'QRIS',
            'agree_terms' => '1',
        ]);

        $payResponse->assertRedirect();

        // Verify Event A category reserved_count increased by 2
        $this->assertEquals(2, $this->categoryA->fresh()->reserved_count);
        $this->assertEquals($initialQuotaA - 2, $this->categoryA->fresh()->remaining_quota);

        // Verify Event B category is 100% UNTOUCHED
        $this->assertEquals(0, $this->categoryB->fresh()->reserved_count);
        $this->assertEquals($initialQuotaB, $this->categoryB->fresh()->remaining_quota);
    }

    /**
     * Test 5: Order lookup isolation across dedicated domains
     */
    public function test_cross_domain_order_lookup_is_isolated(): void
    {
        // Create transaction under Event B
        $trxB = Transaction::create([
            'event_id' => $this->eventB->id,
            'invoice_number' => 'INV-EVENT-B-999',
            'tripay_merchant_ref' => 'TRX-EVENT-B-999',
            'buyer_name' => 'Participant B',
            'buyer_email' => 'b@example.com',
            'buyer_phone' => '081200000000',
            'subtotal' => 300000,
            'fee_amount' => 4500,
            'grand_total' => 304500,
            'payment_method' => 'QRIS',
            'payment_channel_code' => 'QRIS',
            'status' => 'UNPAID',
            'expires_at' => now()->addMinutes(60),
        ]);

        // Attempt to view Event B's order through Domain A -> Must 404 (Not Found on this domain)
        $responseOnDomainA = $this->get('http://tiket.marathon2026.com/order/' . $trxB->invoice_number);
        $responseOnDomainA->assertStatus(404);

        // View Event B's order through Domain B -> Must 200 OK
        $responseOnDomainB = $this->get('http://tiket.jakartanightrun.id/order/' . $trxB->invoice_number);
        $responseOnDomainB->assertStatus(200);
        $responseOnDomainB->assertSee('INV-EVENT-B-999');
        $responseOnDomainB->assertSee('Jakarta Night 10K');
    }
}
