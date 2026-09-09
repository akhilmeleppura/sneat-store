<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecurityPwaAndOpenApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test PWA manifest.json exists and contains valid structure.
     */
    public function test_pwa_manifest_exists_and_is_valid()
    {
        $manifestPath = public_path('manifest.json');
        $this->assertTrue(File::exists($manifestPath), 'manifest.json must exist in public directory');

        $content = json_decode(File::get($manifestPath), true);
        $this->assertIsArray($content);
        $this->assertEquals('AK-Mart Enterprise Store', $content['name']);
        $this->assertEquals('standalone', $content['display']);
        $this->assertNotEmpty($content['icons']);
    }

    /**
     * Test PWA service worker exists in public.
     */
    public function test_pwa_service_worker_exists()
    {
        $swPath = public_path('sw.js');
        $this->assertTrue(File::exists($swPath), 'sw.js must exist in public directory');

        $swContent = File::get($swPath);
        $this->assertStringContainsString('sneat-store-v1', $swContent);
        $this->assertStringContainsString('addEventListener', $swContent);
    }

    /**
     * Test Security Headers are applied on web responses.
     */
    public function test_security_headers_are_applied_to_web_responses()
    {
        $response = $this->get('/faq');
        $response->assertStatus(200);

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertTrue($response->headers->has('Permissions-Policy'));
    }

    /**
     * Test OpenAPI 3.0 specification endpoint.
     */
    public function test_openapi_spec_endpoint_returns_valid_v3_json()
    {
        $response = $this->getJson('/api/v1/openapi.json');

        $response->assertStatus(200)
            ->assertJsonPath('openapi', '3.0.3')
            ->assertJsonPath('info.title', 'AK-Mart Omnichannel Headless REST API')
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'version', 'description'],
                'paths' => [
                    '/store/info',
                    '/store/products',
                    '/store/products/{slug}',
                    '/store/search',
                    '/store/cart',
                    '/store/orders/track',
                ],
            ]);
    }

    /**
     * Test Store Health command runs successfully with --deep option.
     */
    public function test_store_health_command_runs_deep_inspection()
    {
        $this->artisan('store:health', ['--deep' => true])
            ->expectsOutputToContain('SNEAT STORE ENTERPRISE READINESS & HEALTH CHECK')
            ->expectsOutputToContain('Storage & Cache Writeability')
            ->expectsOutputToContain('PWA & Offline Capability')
            ->assertExitCode(0);
    }
}
