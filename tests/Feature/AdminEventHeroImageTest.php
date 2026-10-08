<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminEventHeroImageTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin@regrun.test',
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_create_event_with_hero_image_upload(): void
    {
        $fakeImage = UploadedFile::fake()->image('marathon_hero.jpg', 1920, 1080);

        $response = $this->actingAs($this->adminUser)->post(route('admin.events.store'), [
            'title' => 'Flores Charity Run 2026',
            'slug' => 'flores-charity-run-2026',
            'race_date' => '2026-11-20',
            'race_start_time' => '06:00',
            'venue_name' => 'Lapangan Purna MTQ Pekanbaru',
            'venue_address' => 'Jl. Jenderal Sudirman',
            'hero_image' => $fakeImage,
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHas('success');

        $event = Event::where('slug', 'flores-charity-run-2026')->first();
        $this->assertNotNull($event);
        $this->assertNotNull($event->hero_image);
        $this->assertStringContainsString('uploads/events/hero_flores-charity-run-2026_', $event->hero_image);
        $this->assertFileExists(public_path($event->hero_image));

        // Clean up created fake file
        @unlink(public_path($event->hero_image));
    }

    public function test_admin_can_update_event_hero_image_and_delete_old_file(): void
    {
        $firstImage = UploadedFile::fake()->image('first_hero.jpg', 1200, 600);

        $this->actingAs($this->adminUser)->post(route('admin.events.store'), [
            'title' => 'Lari Pagi Test',
            'slug' => 'lari-pagi-test',
            'race_date' => '2026-10-25',
            'race_start_time' => '06:00',
            'venue_name' => 'Stadion Kaharuddin',
            'hero_image' => $firstImage,
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $event = Event::where('slug', 'lari-pagi-test')->first();
        $oldImagePath = $event->hero_image;
        $this->assertFileExists(public_path($oldImagePath));

        // Upload replacement image
        $replacementImage = UploadedFile::fake()->image('replacement_hero.png', 1920, 1080);

        $response = $this->actingAs($this->adminUser)->post(route('admin.events.update', ['id' => $event->id]), [
            'title' => 'Lari Pagi Test (Updated)',
            'slug' => 'lari-pagi-test',
            'race_date' => '2026-10-25',
            'race_start_time' => '06:00',
            'venue_name' => 'Stadion Kaharuddin Baru',
            'hero_image' => $replacementImage,
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHas('success');

        $event->refresh();
        $this->assertNotEquals($oldImagePath, $event->hero_image);
        $this->assertFileDoesNotExist(public_path($oldImagePath));
        $this->assertFileExists(public_path($event->hero_image));

        // Test delete hero image checkbox
        $this->actingAs($this->adminUser)->post(route('admin.events.update', ['id' => $event->id]), [
            'title' => 'Lari Pagi Test (No Image)',
            'slug' => 'lari-pagi-test',
            'race_date' => '2026-10-25',
            'race_start_time' => '06:00',
            'venue_name' => 'Stadion Kaharuddin Baru',
            'delete_hero_image' => 1,
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $newImagePath = $event->hero_image;
        $event->refresh();
        $this->assertNull($event->hero_image);
        $this->assertFileDoesNotExist(public_path($newImagePath));
    }

    public function test_homepage_renders_hero_image_of_active_default_event(): void
    {
        $event = Event::create([
            'title' => 'Pekanbaru Charity Run 2026',
            'slug' => 'pekanbaru-charity-run',
            'race_date' => '2026-12-01',
            'race_start_time' => '06:00:00',
            'venue_name' => 'Purna MTQ Riau',
            'hero_image' => 'uploads/events/sample_hero.jpg',
            'is_default' => true,
            'is_active' => true,
        ]);

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Pekanbaru Charity Run 2026');
        $response->assertSee(asset('uploads/events/sample_hero.jpg'), false);
    }
}
