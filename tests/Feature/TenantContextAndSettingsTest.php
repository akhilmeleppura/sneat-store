<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Context\Models\TenantSetting;
use Tests\TestCase;

class TenantContextAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Tenant $tenantA;
    protected Store $storeA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);

        $this->tenantA = Tenant::where('slug', 'sneat-global')->firstOrFail();
        $this->storeA = Store::where('slug', 'sneat-flagship')->firstOrFail();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@sneat.com'],
            [
                'name'      => 'Admin User',
                'password'  => bcrypt('password'),
                'tenant_id' => $this->tenantA->id,
            ]
        );
    }

    protected function tearDown(): void
    {
        Context::clear();
        parent::tearDown();
    }

    public function test_can_switch_tenant_context_and_persist_in_session(): void
    {
        // Create second tenant with store and branch
        $tenantB = Tenant::create([
            'name'   => 'Nordic Retail Group',
            'slug'   => 'nordic-retail',
            'status' => 'active',
        ]);

        $storeB = Store::create([
            'tenant_id'  => $tenantB->id,
            'name'       => 'Oslo Flagship',
            'slug'       => 'oslo-flagship',
            'is_default' => true,
        ]);

        $branchB = Branch::create([
            'tenant_id'  => $tenantB->id,
            'store_id'   => $storeB->id,
            'name'       => 'Oslo Central Warehouse',
            'slug'       => 'oslo-hub',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('context.switch.tenant', $tenantB->id));

        $response->assertRedirect();
        $response->assertSessionHas('current_tenant_id', $tenantB->id);
        $response->assertSessionHas('current_store_id', $storeB->id);
        $response->assertSessionHas('current_branch_id', $branchB->id);

        $this->assertEquals($tenantB->id, Context::tenantId());
        $this->assertEquals($storeB->id, Context::storeId());
    }

    public function test_can_switch_store_context(): void
    {
        // Create secondary store under tenant A
        $secondStore = Store::create([
            'tenant_id'  => $this->tenantA->id,
            'name'       => 'Sneat Downtown Outlet',
            'slug'       => 'sneat-downtown',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->admin)->get(route('context.switch.store', $secondStore->id));

        $response->assertRedirect();
        $response->assertSessionHas('current_store_id', $secondStore->id);
        $this->assertEquals($secondStore->id, Context::storeId());
    }

    public function test_admin_can_view_tenant_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.tenant.settings'));

        $response->assertStatus(200);
        $response->assertSee('Tenant & Store Settings', false);
        $response->assertSee($this->tenantA->name);
        $response->assertSee('Company & Regional Preferences', false);
        $response->assertSee('E-Commerce & Checkout Configuration', false);
        $response->assertSee('Security & Encrypted API Integrations', false);
    }

    public function test_admin_can_update_tenant_settings_and_encrypt_secrets(): void
    {
        $payload = [
            'company_name'            => 'Sneat Global Enterprise Inc.',
            'contact_email'           => 'admin@sneatglobal.com',
            'contact_phone'           => '+1 (800) 555-0199',
            'address'                 => '500 Fifth Avenue, Floor 32, New York, NY',
            'timezone'                => 'America/New_York',
            'default_currency'        => 'USD',
            'order_prefix'            => 'GLB-',
            'tax_mode'                => 'inclusive',
            'enable_guest_checkout'   => '1',
            'low_stock_threshold'     => 12,
            'free_shipping_threshold' => 200.00,
            'store_tagline'           => 'Next-gen global commerce at scale',
            'invoice_footer'          => 'All sales subject to standard terms and conditions.',
            'support_hours'           => '24/7 Priority Support',
            'return_policy_days'      => 45,
            'webhook_secret'          => 'whsec_enterprise_secret_xyz123',
            'payment_gateway_api_key' => 'sk_live_very_secret_gateway_token',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.tenant.settings.update'), $payload);

        $response->assertRedirect(route('admin.tenant.settings'));
        $response->assertSessionHas('success');

        $this->tenantA->refresh();

        // Verify standard settings
        $this->assertEquals('Sneat Global Enterprise Inc.', $this->tenantA->getSetting('company_name'));
        $this->assertEquals('admin@sneatglobal.com', $this->tenantA->getSetting('contact_email'));
        $this->assertEquals('GLB-', $this->tenantA->getSetting('order_prefix', null, 'ecommerce'));
        $this->assertEquals('inclusive', $this->tenantA->getSetting('tax_mode', null, 'ecommerce'));
        $this->assertEquals('12', $this->tenantA->getSetting('low_stock_threshold', null, 'ecommerce'));
        $this->assertEquals(45, (int) $this->tenantA->getSetting('return_policy_days', null, 'branding'));

        // Verify encrypted settings at rest
        $rawSetting = TenantSetting::where('tenant_id', $this->tenantA->id)
            ->where('group', 'integrations')
            ->where('key', 'webhook_secret')
            ->first();

        $this->assertNotNull($rawSetting);
        $this->assertTrue((bool) $rawSetting->is_encrypted);
        // Raw database column must NOT equal plaintext secret
        $this->assertNotEquals('whsec_enterprise_secret_xyz123', $rawSetting->value);

        // Accessor on Tenant model automatically decrypts
        $this->assertEquals('whsec_enterprise_secret_xyz123', $this->tenantA->getSetting('webhook_secret', null, 'integrations'));
        $this->assertEquals('sk_live_very_secret_gateway_token', $this->tenantA->getSetting('payment_gateway_api_key', null, 'integrations'));
    }

    public function test_navbar_displays_context_switcher_dropdown(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.currencies.index'));

        $response->assertStatus(200);
        $response->assertSee($this->tenantA->name);
        $response->assertSee('Tenant & Store Settings', false);
        $response->assertSee(route('context.switch.store', $this->storeA->id));
    }
}
