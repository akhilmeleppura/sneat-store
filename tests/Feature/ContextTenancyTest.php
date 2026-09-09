<?php

namespace Tests\Feature;

use App\Helpers\RbacHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Context\Models\TenantSetting;
use Tests\TestCase;

class ContextTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Context::clear();
        parent::tearDown();
    }

    public function test_tenant_creation_and_settings_helper(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('tenants', ['slug' => 'acme']);

        // Test settings helper
        $tenant->setSetting('site_title', 'Acme Store Official');
        $this->assertEquals('Acme Store Official', $tenant->getSetting('site_title'));

        // Test encrypted setting
        $tenant->setSetting('api_key', 'secret_token_123', 'integrations', true);
        $this->assertEquals('secret_token_123', $tenant->getSetting('api_key', null, 'integrations'));

        // Clean up
        $tenant->forceDelete();
    }

    public function test_context_service_hierarchy_resolution(): void
    {
        $tenant = Tenant::create([
            'name' => 'Apex Retail',
            'slug' => 'apex',
            'status' => 'active',
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => 'Apex Flagship',
            'slug' => 'flagship',
            'is_default' => true,
        ]);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'store_id' => $store->id,
            'name' => 'Downtown Branch',
            'slug' => 'downtown',
            'is_default' => true,
        ]);

        // Manually set context
        Context::setTenant($tenant);
        Context::setStore($store);
        Context::setBranch($branch);

        $this->assertTrue(Context::hasTenant());
        $this->assertEquals($tenant->id, Context::tenantId());
        $this->assertEquals($store->id, Context::storeId());
        $this->assertEquals($branch->id, Context::branchId());

        // Clean up
        $branch->forceDelete();
        $store->forceDelete();
        $tenant->forceDelete();
    }

    public function test_uses_tenant_trait_scopes_queries_and_auto_sets_id(): void
    {
        $tenantA = Tenant::create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $tenantB = Tenant::create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        // Set context to Tenant A
        Context::setTenant($tenantA);

        // Create store with context active (tenant_id should be auto-filled if empty)
        $storeA = Store::create([
            'name' => 'Store A',
            'slug' => 'store-a',
        ]);

        $this->assertEquals($tenantA->id, $storeA->tenant_id);

        // Switch to Tenant B and create Store B
        Context::setTenant($tenantB);
        $storeB = Store::create([
            'name' => 'Store B',
            'slug' => 'store-b',
        ]);
        $this->assertEquals($tenantB->id, $storeB->tenant_id);

        // While in Tenant B, Store::all() should only return Store B!
        $visibleStores = Store::all();
        $this->assertTrue($visibleStores->contains('id', $storeB->id));
        $this->assertFalse($visibleStores->contains('id', $storeA->id));

        // When querying withoutTenancy(), all stores are visible
        $allStores = Store::withoutTenancy()->get();
        $this->assertTrue($allStores->contains('id', $storeA->id));
        $this->assertTrue($allStores->contains('id', $storeB->id));

        // Clean up
        $storeA->forceDelete();
        $storeB->forceDelete();
        $tenantA->forceDelete();
        $tenantB->forceDelete();
    }

    public function test_rbac_helper_prefixing_and_extraction(): void
    {
        $scopedRole = RbacHelper::tenantRole('admin', 42);
        $this->assertEquals('tenant.42.admin', $scopedRole);
        $this->assertEquals('admin', RbacHelper::stripTenantPrefix($scopedRole));
        $this->assertEquals(42, RbacHelper::extractTenantId($scopedRole));
        $this->assertTrue(RbacHelper::isTenantScoped($scopedRole));
        $this->assertFalse(RbacHelper::isTenantScoped('platform-admin'));
    }
}
