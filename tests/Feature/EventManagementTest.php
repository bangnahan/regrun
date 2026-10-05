<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_events_page()
    {
        $admin = User::first();

        $response = $this->actingAs($admin)->get(route('admin.events'));

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Event Lari &amp; Tiket', false);
        $response->assertSee('Nusantara Sunset Run 2026');
        $response->assertSee('Tambah Event Baru');
    }

    public function test_admin_can_create_new_event()
    {
        $admin = User::first();

        $payload = [
            'title' => 'Bali Coastal Ultra 2026',
            'slug' => 'bali-coastal-ultra-2026',
            'race_date' => '2026-11-20',
            'race_start_time' => '05:30',
            'venue_name' => 'Pantai Kuta Bali',
            'venue_address' => 'Badung, Bali',
            'rpc_start_date' => '2026-11-18',
            'rpc_end_date' => '2026-11-19',
            'rpc_location' => 'Kuta Beach Hotel Ballroom',
            'custom_domain' => 'balirun.regrun.test',
            'description' => 'Event ultra marathon pesisir pantai Bali.',
            'is_active' => '1',
            'is_default' => '0',
            'seed_default_categories' => '1',
        ];

        $response = $this->actingAs($admin)->post(route('admin.events.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'title' => 'Bali Coastal Ultra 2026',
            'slug' => 'bali-coastal-ultra-2026',
            'venue_name' => 'Pantai Kuta Bali',
            'custom_domain' => 'balirun.regrun.test',
        ]);

        $event = Event::where('slug', 'bali-coastal-ultra-2026')->first();
        $this->assertCount(2, $event->ticketCategories);
    }

    public function test_admin_can_update_event_details()
    {
        $admin = User::first();
        $event = Event::first();

        $payload = [
            'title' => 'Nusantara Sunset Run 2026 (Updated)',
            'slug' => 'sunset-run-2026-updated',
            'race_date' => '2026-10-25',
            'race_start_time' => '16:00',
            'venue_name' => 'PIK 2 White Sand Beach',
            'venue_address' => 'Jakarta Utara',
            'rpc_start_date' => '2026-10-22',
            'rpc_end_date' => '2026-10-24',
            'rpc_location' => 'PIK Avenue Lt. 2',
            'custom_domain' => 'sunset.regrun.test',
            'description' => 'Updated description.',
            'is_active' => '1',
            'is_default' => '1',
        ];

        $response = $this->actingAs($admin)->post(route('admin.events.update', ['id' => $event->id]), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Nusantara Sunset Run 2026 (Updated)',
            'venue_name' => 'PIK 2 White Sand Beach',
            'race_start_time' => '16:00',
        ]);
    }

    public function test_admin_can_add_ticket_category_to_event()
    {
        $admin = User::first();
        $event = Event::first();

        $payload = [
            'name' => '42K Full Marathon',
            'code' => '42K',
            'quota' => 250,
            'price' => 550000,
            'early_bird_price' => 450000,
            'early_bird_end_date' => '2026-10-15 23:59:00',
            'min_age' => 18,
            'description' => 'Kategori full marathon 42.195 km',
            'sort_order' => 4,
            'is_active' => '1',
        ];

        $response = $this->actingAs($admin)->post(route('admin.events.categories.store', ['id' => $event->id]), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ticket_categories', [
            'event_id' => $event->id,
            'code' => '42K',
            'name' => '42K Full Marathon',
            'quota' => 250,
            'price' => 550000,
        ]);
    }

    public function test_admin_can_update_and_delete_unused_category()
    {
        $admin = User::first();
        $event = Event::first();

        $category = TicketCategory::create([
            'event_id' => $event->id,
            'name' => 'Kids Dash 1K',
            'code' => 'KIDS1K',
            'quota' => 100,
            'price' => 100000,
            'sold_count' => 0,
            'reserved_count' => 0,
            'is_active' => true,
        ]);

        // Update
        $responseUpdate = $this->actingAs($admin)->post(route('admin.category.update', ['id' => $category->id]), [
            'name' => 'Kids Dash 1.5K',
            'code' => 'KIDS15K',
            'quota' => 150,
            'price' => 120000,
            'early_bird_price' => 95000,
            'is_active' => '1',
        ]);
        $responseUpdate->assertRedirect();
        $this->assertDatabaseHas('ticket_categories', ['id' => $category->id, 'name' => 'Kids Dash 1.5K', 'quota' => 150]);

        // Delete
        $responseDelete = $this->actingAs($admin)->post(route('admin.category.delete', ['id' => $category->id]));
        $responseDelete->assertRedirect();
        $this->assertDatabaseMissing('ticket_categories', ['id' => $category->id]);
    }
}
