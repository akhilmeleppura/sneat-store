<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoAndGdprTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_sitemap_xml_generation()
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringContainsString('xml', $response->headers->get('Content-Type') ?: '');
        $response->assertSee('<urlset', false);
    }

    public function test_robots_txt_generation()
    {
        $response = $this->get('/robots.txt');

        $response->assertStatus(200);
        $response->assertSee('User-agent: *');
        $response->assertSee('Sitemap:');
    }

    public function test_faq_help_center_page_renders()
    {
        $response = $this->get('/faq');

        $response->assertStatus(200);
        $response->assertSee('Frequently Asked Questions');
        $response->assertSee('Orders & Shipping');
    }

    public function test_gdpr_data_export_and_deletion()
    {
        $user = User::first() ?: User::factory()->create();

        // 1. Export Data Archive
        $exportResponse = $this->actingAs($user)->get('/account/privacy/export-data');
        $exportResponse->assertStatus(200)
            ->assertHeader('Content-Disposition')
            ->assertJsonStructure([
                'subject',
                'exported_at',
                'account' => ['id', 'name', 'email'],
                'orders',
                'product_reviews',
            ]);

        // 2. Request Erasure
        $deleteResponse = $this->actingAs($user)->postJson('/account/privacy/request-deletion');
        $deleteResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);
    }
}
