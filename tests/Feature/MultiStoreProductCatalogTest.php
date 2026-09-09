<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Tests\TestCase;

class MultiStoreProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Tenant $tenant;
    protected Store $flagshipStore;
    protected Store $outletStore;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);

        $this->tenant = Tenant::where('slug', 'sneat-global')->firstOrFail();
        $this->flagshipStore = Store::where('slug', 'sneat-flagship')->firstOrFail();

        // Create secondary store under same tenant
        $this->outletStore = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Sneat Outlet Center',
            'slug'       => 'sneat-outlet',
            'status'     => 'active',
            'is_default' => false,
        ]);

        $this->admin = User::firstOrCreate(
            ['email' => 'admin@sneat.com'],
            [
                'name'      => 'Admin User',
                'password'  => bcrypt('password'),
                'tenant_id' => $this->tenant->id,
            ]
        );

        $this->category = Category::firstOrCreate(
            ['slug' => 'electronics'],
            ['name' => 'Electronics', 'status' => 'active']
        );
    }

    protected function tearDown(): void
    {
        Context::clear();
        parent::tearDown();
    }

    public function test_admin_can_create_product_with_multi_store_assignments_and_price_overrides(): void
    {
        $payload = [
            'name'             => 'Pro Studio Microphone',
            'sku'              => 'MIC-STUDIO-01',
            'category_id'      => $this->category->id,
            'price'            => 199.00,
            'compare_at_price' => 249.00,
            'cost_price'       => 90.00,
            'type'             => 'simple',
            'short_description'=> 'Broadcast-grade studio microphone.',
            'description'      => 'Full condenser microphone specs.',
            'status'           => 'published',
            'initial_stock'    => 20,
            'store_ids'        => [$this->flagshipStore->id, $this->outletStore->id],
            'store_prices'     => [
                $this->flagshipStore->id => '', // Inherit base price 199.00
                $this->outletStore->id   => 169.00, // Discounted for outlet store
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('catalog.products.store'), $payload);

        $response->assertRedirect(route('catalog.products.index'));
        $response->assertSessionHas('success');

        $product = Product::where('sku', 'MIC-STUDIO-01')->firstOrFail();

        // Assert assignments in product_store
        $this->assertDatabaseHas('product_store', [
            'product_id'     => $product->id,
            'store_id'       => $this->flagshipStore->id,
            'is_visible'     => 1,
            'price_override' => null,
        ]);

        $this->assertDatabaseHas('product_store', [
            'product_id'     => $product->id,
            'store_id'       => $this->outletStore->id,
            'is_visible'     => 1,
            'price_override' => 169.00,
        ]);

        // Test effective price in different store contexts
        Context::setStore($this->flagshipStore);
        $this->assertEquals(199.00, $product->getEffectivePrice());

        Context::setStore($this->outletStore);
        $this->assertEquals(169.00, $product->getEffectivePrice());
    }

    public function test_product_edit_page_renders_with_multi_store_channels(): void
    {
        $product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $this->category->id,
            'name'        => 'Wireless Gaming Headset',
            'slug'        => 'wireless-gaming-headset-test',
            'sku'         => 'HEADSET-TEST-01',
            'type'        => 'simple',
            'price'       => 120.00,
            'status'      => 'published',
        ]);

        $response = $this->actingAs($this->admin)->get(route('catalog.products.edit', $product->id));

        $response->assertStatus(200);
        $response->assertSee('Multi-Store Channels & Price Overrides', false);
        $response->assertSee($this->flagshipStore->name);
        $response->assertSee($this->outletStore->name);
    }

    public function test_admin_can_update_product_and_sync_stores(): void
    {
        $product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $this->category->id,
            'name'        => 'Ergonomic Desk Chair',
            'slug'        => 'ergonomic-desk-chair-test',
            'sku'         => 'CHAIR-TEST-01',
            'type'        => 'simple',
            'price'       => 299.00,
            'status'      => 'published',
        ]);

        // Update product to only belong to outlet store with custom price
        $payload = [
            'name'             => 'Ergonomic Desk Chair Pro',
            'sku'              => 'CHAIR-TEST-01',
            'category_id'      => $this->category->id,
            'price'            => 299.00,
            'status'           => 'published',
            'store_ids'        => [$this->outletStore->id],
            'store_prices'     => [
                $this->outletStore->id => 249.50,
            ],
        ];

        $response = $this->actingAs($this->admin)->put(route('catalog.products.update', $product->id), $payload);

        $response->assertRedirect(route('catalog.products.index'));

        // Flagship should not have this product
        $this->assertDatabaseMissing('product_store', [
            'product_id' => $product->id,
            'store_id'   => $this->flagshipStore->id,
        ]);

        // Outlet store should have override 249.50
        $this->assertDatabaseHas('product_store', [
            'product_id'     => $product->id,
            'store_id'       => $this->outletStore->id,
            'price_override' => 249.50,
        ]);
    }

    public function test_storefront_scope_for_store(): void
    {
        $productFlagshipOnly = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $this->category->id,
            'name'        => 'Flagship Exclusive Watch',
            'slug'        => 'flagship-exclusive-watch',
            'sku'         => 'WATCH-FLAGSHIP',
            'type'        => 'simple',
            'price'       => 500.00,
            'status'      => 'published',
        ]);

        $productFlagshipOnly->stores()->sync([
            $this->flagshipStore->id => ['is_visible' => true],
        ]);

        // In Flagship Store, product should be visible
        $visibleInFlagship = Product::forStore($this->flagshipStore->id)->pluck('id');
        $this->assertTrue($visibleInFlagship->contains($productFlagshipOnly->id));

        // In Outlet Store, product should NOT be visible
        $visibleInOutlet = Product::forStore($this->outletStore->id)->pluck('id');
        $this->assertFalse($visibleInOutlet->contains($productFlagshipOnly->id));
    }
}
