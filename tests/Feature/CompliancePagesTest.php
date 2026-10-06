<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompliancePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_view_jelatix_homepage()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Jelatix');
        $response->assertSee('Nusantara Sunset Run 2026');
        $response->assertSee('5K Fun Run');
    }

    public function test_can_view_terms_and_conditions_page()
    {
        $response = $this->get('/terms');
        $response->assertStatus(200);
        $response->assertSee('Syarat &amp; Ketentuan', false);
        $response->assertSee('Tripay');
        $response->assertSee('jelatix.com');

        $aliasResponse = $this->get('/syarat-ketentuan');
        $aliasResponse->assertStatus(200);
    }

    public function test_can_view_privacy_policy_page()
    {
        $response = $this->get('/privacy-policy');
        $response->assertStatus(200);
        $response->assertSee('Kebijakan Privasi');
        $response->assertSee('UU PDP');
        $response->assertSee('Tripay');

        $aliasResponse = $this->get('/kebijakan-privasi');
        $aliasResponse->assertStatus(200);
    }

    public function test_can_view_refund_policy_page()
    {
        $response = $this->get('/refund-policy');
        $response->assertStatus(200);
        $response->assertSee('Kebijakan Pengembalian Dana');
        $response->assertSee('Non-Refundable');
        $response->assertSee('Tripay');

        $aliasResponse = $this->get('/kebijakan-pengembalian');
        $aliasResponse->assertStatus(200);
    }

    public function test_can_view_contact_page()
    {
        $response = $this->get('/contact');
        $response->assertStatus(200);
        $response->assertSee('Hubungi Kami');
        $response->assertSee('hi@jelatix.com');
        $response->assertSee('0819-1644-4458');
        $response->assertSee('Pekanbaru');

        $aliasResponse = $this->get('/kontak');
        $aliasResponse->assertStatus(200);
    }
}
