<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Tests\TestCase;

class AdminContextHubTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);

        $this->tenant = Tenant::where('slug', 'sneat-global')->firstOrFail();
        $this->store = Store::where('slug', 'sneat-flagship')->firstOrFail();
        $this->branch = Branch::where('store_id', $this->store->id)->firstOrFail();

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@sneat.com'],
            [
                'name'      => 'Admin User',
                'password'  => bcrypt('password'),
                'tenant_id' => $this->tenant->id,
            ]
        );
    }

    protected function tearDown(): void
    {
        Context::clear();
        parent::tearDown();
    }

    public function test_admin_context_hub_screen_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.context.index'));

        $response->assertStatus(200);
        $response->assertSee('Store & Tenant Management Hub', false);
        $response->assertSee('Total Tenants');
        $response->assertSee('Active Stores');
        $response->assertSee('Retail Branches');
        $response->assertSee($this->tenant->name);
    }

    public function test_tenant_creation_auto_provisions_store_and_branch(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.context.tenants.store'), [
                'name'     => 'Apex Dynamics Ltd',
                'slug'     => 'apex-dynamics',
                'domain'   => 'apex.example.com',
                'status'   => 'active',
                'timezone' => 'America/New_York',
                'currency' => 'USD',
                'locale'   => 'en',
            ]);

        $response->assertRedirect(route('admin.context.index', ['tab' => 'tenants']));
        $response->assertSessionHas('success');

        $tenant = Tenant::where('slug', 'apex-dynamics')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('Apex Dynamics Ltd', $tenant->name);
        $this->assertEquals('apex.example.com', $tenant->domain);

        // Verify auto-provisioned store
        $store = Store::withoutTenancy()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($store);
        $this->assertTrue($store->is_default);

        // Verify auto-provisioned branch
        $branch = Branch::withoutTenancy()->where('store_id', $store->id)->first();
        $this->assertNotNull($branch);
        $this->assertTrue($branch->is_default);
    }

    public function test_tenant_can_be_updated_and_deleted(): void
    {
        $tenant = Tenant::create([
            'name'   => 'Beta Retail Co',
            'slug'   => 'beta-retail',
            'status' => 'active',
        ]);

        $updateResponse = $this->actingAs($this->admin)
            ->put(route('admin.context.tenants.update', $tenant->id), [
                'name'     => 'Beta Retail Group Updated',
                'slug'     => 'beta-retail-group',
                'domain'   => 'beta.example.com',
                'status'   => 'inactive',
                'timezone' => 'Europe/London',
                'currency' => 'GBP',
                'locale'   => 'en',
            ]);

        $updateResponse->assertRedirect(route('admin.context.index', ['tab' => 'tenants']));
        $tenant->refresh();
        $this->assertEquals('Beta Retail Group Updated', $tenant->name);
        $this->assertEquals('inactive', $tenant->status);

        // Test delete
        $deleteResponse = $this->actingAs($this->admin)
            ->delete(route('admin.context.tenants.delete', $tenant->id));

        $deleteResponse->assertRedirect(route('admin.context.index', ['tab' => 'tenants']));
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }

    public function test_store_can_be_created_updated_and_deleted(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.context.stores.store'), [
                'tenant_id'  => $this->tenant->id,
                'name'       => 'Outlet Store North',
                'slug'       => 'outlet-north',
                'domain'     => 'outlet.example.com',
                'is_default' => 0,
                'status'     => 'active',
            ]);

        $response->assertRedirect(route('admin.context.index', ['tab' => 'stores']));
        $store = Store::withoutTenancy()->where('slug', 'outlet-north')->first();
        $this->assertNotNull($store);
        $this->assertEquals('Outlet Store North', $store->name);

        // Update Store
        $updateResponse = $this->actingAs($this->admin)
            ->put(route('admin.context.stores.update', $store->id), [
                'name'       => 'Outlet Store Premier',
                'slug'       => 'outlet-premier',
                'domain'     => 'outlet-premier.example.com',
                'is_default' => 0,
                'status'     => 'active',
            ]);

        $updateResponse->assertRedirect(route('admin.context.index', ['tab' => 'stores']));
        $store->refresh();
        $this->assertEquals('Outlet Store Premier', $store->name);

        // Delete Store
        $deleteResponse = $this->actingAs($this->admin)
            ->delete(route('admin.context.stores.delete', $store->id));

        $deleteResponse->assertRedirect(route('admin.context.index', ['tab' => 'stores']));
        $this->assertSoftDeleted('stores', ['id' => $store->id]);
    }

    public function test_branch_can_be_created_updated_and_deleted(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.context.branches.store'), [
                'store_id'    => $this->store->id,
                'name'        => 'Uptown Boutique',
                'slug'        => 'uptown-boutique',
                'code'        => 'BR-UPTOWN-01',
                'address'     => '750 5th Avenue',
                'city'        => 'New York',
                'state'       => 'NY',
                'country'     => 'USA',
                'postal_code' => '10019',
                'phone'       => '+1 212 555 0199',
                'email'       => 'uptown@example.com',
                'is_default'  => 0,
                'status'      => 'active',
            ]);

        $response->assertRedirect(route('admin.context.index', ['tab' => 'branches']));
        $branch = Branch::withoutTenancy()->where('code', 'BR-UPTOWN-01')->first();
        $this->assertNotNull($branch);
        $this->assertEquals('Uptown Boutique', $branch->name);
        $this->assertEquals('New York', $branch->city);

        // Update Branch
        $updateResponse = $this->actingAs($this->admin)
            ->put(route('admin.context.branches.update', $branch->id), [
                'name'        => 'Uptown Flagship Boutique',
                'slug'        => 'uptown-flagship',
                'code'        => 'BR-UPTOWN-01',
                'address'     => '750 5th Avenue Suite 200',
                'city'        => 'New York',
                'state'       => 'NY',
                'country'     => 'USA',
                'postal_code' => '10019',
                'phone'       => '+1 212 555 0200',
                'email'       => 'uptown-flagship@example.com',
                'is_default'  => 0,
                'status'      => 'active',
            ]);

        $updateResponse->assertRedirect(route('admin.context.index', ['tab' => 'branches']));
        $branch->refresh();
        $this->assertEquals('Uptown Flagship Boutique', $branch->name);

        // Delete Branch
        $deleteResponse = $this->actingAs($this->admin)
            ->delete(route('admin.context.branches.delete', $branch->id));

        $deleteResponse->assertRedirect(route('admin.context.index', ['tab' => 'branches']));
        $this->assertSoftDeleted('tenant_branches', ['id' => $branch->id]);
    }

    public function test_quick_switch_context_changes_session(): void
    {
        $tenantB = Tenant::create([
            'name'   => 'Delta Enterprises',
            'slug'   => 'delta-corp',
            'status' => 'active',
        ]);

        $storeB = Store::create([
            'tenant_id'  => $tenantB->id,
            'name'       => 'Delta Main Store',
            'slug'       => 'delta-main',
            'is_default' => true,
            'status'     => 'active',
        ]);

        $branchB = Branch::create([
            'tenant_id'  => $tenantB->id,
            'store_id'   => $storeB->id,
            'name'       => 'Delta HQ Branch',
            'slug'       => 'delta-hq',
            'code'       => 'DLT-HQ',
            'is_default' => true,
            'status'     => 'active',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.context.switch'), [
                'tenant_id' => $tenantB->id,
                'store_id'  => $storeB->id,
                'branch_id' => $branchB->id,
            ]);

        $response->assertSessionHas('current_tenant_id', $tenantB->id);
        $response->assertSessionHas('current_store_id', $storeB->id);
        $response->assertSessionHas('current_branch_id', $branchB->id);
    }
}
