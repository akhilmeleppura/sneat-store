<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Services\InventoryService;
use Modules\Marketplace\Models\Vendor;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Tests\TestCase;

class StorefrontCustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Storefront Tenant',
            'slug' => 'sf-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Storefront Main Store',
            'slug'       => 'sf-store-' . Str::random(5),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Storefront Central Branch',
            'slug'       => 'sf-branch-' . Str::random(5),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->customerUser = User::create([
            'name'              => 'Jane Customer',
            'email'             => 'jane.customer.' . Str::random(5) . '@sneat.test',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
            'store_id'          => $this->store->id,
        ]);
    }

    /**
     * Test storefront home renders successfully with published products.
     */
    public function test_storefront_home_renders_successfully(): void
    {
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Electronics & Gadgets',
            'slug'      => 'electronics-' . Str::random(4),
            'status'    => 'active',
        ]);

        $product = Product::create([
            'tenant_id'    => $this->tenant->id,
            'category_id'  => $category->id,
            'name'         => 'Wireless Noise-Cancelling Headphones',
            'slug'         => 'wireless-headphones-' . Str::random(4),
            'price'        => 299.99,
            'status'       => 'published',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Wireless Noise-Cancelling Headphones');
        $response->assertSee('Electronics & Gadgets');
    }

    /**
     * Test catalog listing and filtering by category.
     */
    public function test_catalog_listing_and_filtering_by_category(): void
    {
        $catAudio = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Audio Gear',
            'slug'      => 'audio-' . Str::random(4),
            'status'    => 'active',
        ]);

        $catFashion = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Fashion Wear',
            'slug'      => 'fashion-' . Str::random(4),
            'status'    => 'active',
        ]);

        $prodAudio = Product::create([
            'tenant_id'    => $this->tenant->id,
            'category_id'  => $catAudio->id,
            'name'         => 'Studio Pro Headphones',
            'slug'         => 'studio-pro-' . Str::random(4),
            'price'        => 199.00,
            'status'       => 'published',
        ]);

        $prodFashion = Product::create([
            'tenant_id'    => $this->tenant->id,
            'category_id'  => $catFashion->id,
            'name'         => 'Leather Jacket',
            'slug'         => 'leather-jacket-' . Str::random(4),
            'price'        => 350.00,
            'status'       => 'published',
        ]);

        // Filter by Audio Gear
        $response = $this->get('/store/products?category=' . $catAudio->slug);

        $response->assertStatus(200);
        $response->assertSee('Studio Pro Headphones');
        $response->assertDontSee('Leather Jacket');
    }

    /**
     * Test product detail page renders with variants and stock.
     */
    public function test_product_detail_page_renders_with_variants_and_stock(): void
    {
        $product = Product::create([
            'tenant_id'    => $this->tenant->id,
            'name'         => 'Ergonomic Desk Chair',
            'slug'         => 'ergonomic-desk-chair-' . Str::random(4),
            'description'  => 'Premium high-back office chair with lumbar support.',
            'price'        => 450.00,
            'status'       => 'published',
        ]);

        $variant1 = ProductVariant::create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $product->id,
            'sku'        => 'EDC-BLK-' . Str::random(4),
            'price'      => 450.00,
        ]);

        $variant2 = ProductVariant::create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $product->id,
            'sku'        => 'EDC-GRY-' . Str::random(4),
            'price'      => 475.00,
        ]);

        // Add physical stock to branch
        $invService = app(InventoryService::class);
        $invService->adjustStock($variant1->id, $this->branch->id, 25, 'Initial stock');

        $response = $this->get('/store/products/' . $product->slug);

        $response->assertStatus(200);
        $response->assertSee('Ergonomic Desk Chair');
        $response->assertSee('Premium high-back office chair with lumbar support.');
        $response->assertSee($variant1->sku);
        $response->assertSee($variant2->sku);
        $response->assertSee('450.00');
        $response->assertSee('475.00');
    }

    /**
     * Test customer portal requires authentication.
     */
    public function test_customer_portal_requires_authentication(): void
    {
        $dashboardResponse = $this->get('/account/dashboard');
        $dashboardResponse->assertRedirect('/login');

        $ordersResponse = $this->get('/account/orders');
        $ordersResponse->assertRedirect('/login');
    }

    /**
     * Test authenticated customer can view dashboard and strictly their own orders.
     */
    public function test_customer_can_view_dashboard_and_own_orders(): void
    {
        $otherUser = User::create([
            'name'     => 'Other Customer',
            'email'    => 'other.' . Str::random(5) . '@sneat.test',
            'password' => bcrypt('password'),
        ]);

        $myOrderNum = 'ORD-MINE-' . Str::random(6);
        $otherOrderNum = 'ORD-OTHER-' . Str::random(6);

        // Customer's order
        $customerOrder = Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'user_id'          => $this->customerUser->id,
            'order_number'     => $myOrderNum,
            'customer_name'    => $this->customerUser->name,
            'customer_email'   => $this->customerUser->email,
            'status'           => 'processing',
            'subtotal'         => 150.00,
            'tax_amount'       => 15.00,
            'shipping_amount'  => 10.00,
            'grand_total'      => 175.00,
            'currency'         => 'USD',
            'shipping_address' => ['street' => '100 Main St', 'city' => 'Metropolis', 'country' => 'US'],
            'billing_address'  => ['street' => '100 Main St', 'city' => 'Metropolis', 'country' => 'US'],
        ]);

        // Other user's order
        $otherOrder = Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'user_id'          => $otherUser->id,
            'order_number'     => $otherOrderNum,
            'customer_name'    => $otherUser->name,
            'customer_email'   => $otherUser->email,
            'status'           => 'completed',
            'subtotal'         => 800.00,
            'tax_amount'       => 80.00,
            'shipping_amount'  => 20.00,
            'grand_total'      => 900.00,
            'currency'         => 'USD',
            'shipping_address' => ['street' => '999 High St', 'city' => 'Gotham', 'country' => 'US'],
            'billing_address'  => ['street' => '999 High St', 'city' => 'Gotham', 'country' => 'US'],
        ]);

        $response = $this->actingAs($this->customerUser)->get('/account/orders');

        $response->assertStatus(200);
        $response->assertSee($myOrderNum);
        $response->assertDontSee($otherOrderNum);

        // Customer view their own order detail
        $detailResponse = $this->actingAs($this->customerUser)->get('/account/orders/' . $myOrderNum);
        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($myOrderNum);

        // Customer attempts to view other customer's order detail -> 404
        $unauthorizedDetail = $this->actingAs($this->customerUser)->get('/account/orders/' . $otherOrderNum);
        $unauthorizedDetail->assertStatus(404);
    }

    /**
     * Test customer can update their profile.
     */
    public function test_customer_can_update_profile(): void
    {
        $response = $this->actingAs($this->customerUser)->post('/account/profile', [
            'name'  => 'Jane Updated Name',
            'email' => $this->customerUser->email,
            'phone' => '+1-555-0199',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id'   => $this->customerUser->id,
            'name' => 'Jane Updated Name',
        ]);
    }
}
