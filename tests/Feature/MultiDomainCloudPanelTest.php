<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventDomain;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiDomainCloudPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test 1: EventDomain model normalizes domain strings properly
     */
    public function test_domain_normalization(): void
    {
        $this->assertEquals('tiket.nusantararun.com', EventDomain::normalizeDomain('https://tiket.nusantararun.com/'));
        $this->assertEquals('tiket.nusantararun.com', EventDomain::normalizeDomain('http://tiket.nusantararun.com:8080/path/test'));
        $this->assertEquals('marathon.id', EventDomain::normalizeDomain('MARATHON.ID'));
        $this->assertEquals('event.com', EventDomain::normalizeDomain('  https://event.com:443/  '));
    }

    /**
     * Test 2: Multi-domain resolution maps host header to correct Event
     */
    public function test_multi_domain_request_resolves_correct_event(): void
    {
        $defaultEvent = Event::where('is_default', true)->first();

        // Create secondary event with custom domain alias
        $secondEvent = Event::create([
            'title' => 'Jakarta Night 10K',
            'slug' => 'jakarta-night-10k',
            'venue_name' => 'Monas Jakarta',
            'race_date' => now()->addMonths(3),
            'race_start_time' => '19:00:00',
            'is_active' => true,
            'is_default' => false,
        ]);

        EventDomain::create([
            'event_id' => $secondEvent->id,
            'domain' => 'tiket.jakartanightrun.id',
            'is_primary' => true,
            'is_active' => true,
        ]);

        // Request 1: Accessing with Domain 2
        $responseSecond = $this->get('http://tiket.jakartanightrun.id/');

        $responseSecond->assertStatus(200);
        $responseSecond->assertSee('Jakarta Night 10K');
        $this->assertEquals($secondEvent->id, app('currentEvent')->id);

        // Request 2: Accessing with default/unknown domain resolves default event
        $responseDefault = $this->get('http://portal.regrun.test/');

        $responseDefault->assertStatus(200);
        $responseDefault->assertSee($defaultEvent->title);
        $this->assertEquals($defaultEvent->id, app('currentEvent')->id);
    }

    /**
     * Test 3: Admin can add, set primary, and delete domain aliases
     */
    public function test_admin_can_manage_event_domains(): void
    {
        $admin = User::first();
        $event = Event::first();

        // 1. Add new domain alias
        $postResponse = $this->actingAs($admin)->post(route('admin.events.domains.store', ['id' => $event->id]), [
            'domain' => 'https://tiket.marathon2026.com/',
            'is_primary' => '1',
        ]);

        $postResponse->assertRedirect();
        $postResponse->assertSessionHas('success');

        $domainRecord = EventDomain::where('domain', 'tiket.marathon2026.com')->first();
        $this->assertNotNull($domainRecord);
        $this->assertTrue($domainRecord->is_primary);
        $this->assertEquals('tiket.marathon2026.com', $event->fresh()->custom_domain);

        // 2. Add second domain alias and set as primary
        $secondPost = $this->actingAs($admin)->post(route('admin.events.domains.store', ['id' => $event->id]), [
            'domain' => 'reg.marathon2026.com',
            'is_primary' => '0',
        ]);
        $secondPost->assertRedirect();

        $secondDomain = EventDomain::where('domain', 'reg.marathon2026.com')->first();
        $this->assertFalse($secondDomain->is_primary);

        // Set second domain as primary
        $setPrimaryResponse = $this->actingAs($admin)->post(route('admin.events.domains.set_primary', ['id' => $secondDomain->id]));
        $setPrimaryResponse->assertRedirect();

        $this->assertTrue($secondDomain->fresh()->is_primary);
        $this->assertFalse($domainRecord->fresh()->is_primary);
        $this->assertEquals('reg.marathon2026.com', $event->fresh()->custom_domain);

        // 3. Delete domain alias
        $deleteResponse = $this->actingAs($admin)->post(route('admin.events.domains.delete', ['id' => $domainRecord->id]));
        $deleteResponse->assertRedirect();

        $this->assertDatabaseMissing('event_domains', ['id' => $domainRecord->id]);
    }

    /**
     * Test 4: Event-level gateway credentials override hierarchy
     */
    public function test_event_level_gateway_credentials_hierarchy(): void
    {
        $event = Event::first();

        // Case A: No custom credentials set on event -> falls back to global SystemSetting
        $fallbackCreds = $event->getTripayCredentials();
        $this->assertFalse($fallbackCreds['is_custom']);
        $this->assertEquals('T39430', $fallbackCreds['merchant_code']);

        // Case B: Event has custom credentials configured
        $event->update([
            'tripay_merchant_code' => 'T_CUSTOM_ORGANIZER_99',
            'tripay_api_key' => 'DEV_CUSTOM_KEY_XYZ',
            'tripay_private_key' => 'PRIV_CUSTOM_SECRET_XYZ',
        ]);

        $customCreds = $event->fresh()->getTripayCredentials();
        $this->assertTrue($customCreds['is_custom']);
        $this->assertEquals('T_CUSTOM_ORGANIZER_99', $customCreds['merchant_code']);
        $this->assertEquals('DEV_CUSTOM_KEY_XYZ', $customCreds['api_key']);
    }
}
