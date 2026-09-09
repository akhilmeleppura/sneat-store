<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Cart\Services\CartService;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Models\InventoryStock;
use Modules\Order\Models\Coupon;
use Modules\Order\Models\CouponUsage;
use Modules\Order\Models\Order;
use Modules\Order\Services\CheckoutService;
use Modules\Order\Services\PricingEngine;
use Modules\Order\Services\PromotionService;
use Tests\TestCase;

class CouponPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected User $adminUser;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Promotion Test Tenant',
            'slug' => 'promo-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Promotion Store',
            'slug'       => 'promo-store-' . Str::random(5),
            'code'       => 'STR-' . Str::random(3),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Promotion Branch',
            'slug'       => 'promo-branch-' . Str::random(5),
            'code'       => 'BR-' . Str::random(3),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->adminUser = User::create([
            'name'              => 'Promo Admin',
            'email'             => 'admin.promo.' . Str::random(5) . '@sneat.test',
            'password'          => bcrypt('password'),
            'is_supreme_admin'  => true,
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
            'store_id'          => $this->store->id,
            'tenant_branch_id'  => $this->branch->id,
        ]);

        $this->category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Electronics & Gadgets',
            'slug'      => 'electronics-' . Str::random(5),
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $this->category->id,
            'name'        => 'Wireless Headphones',
            'slug'        => 'wireless-headphones-' . Str::random(5),
            'price'       => 100.00,
            'status'      => 'published',
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'HEADPHONES-' . Str::random(4),
            'price'      => 100.00,
        ]);

        InventoryStock::create([
            'tenant_id'          => $this->tenant->id,
            'tenant_branch_id'   => $this->branch->id,
            'product_id'         => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity_on_hand'   => 50,
            'quantity_reserved'  => 0,
            'reorder_level'      => 5,
        ]);
    }

    /**
     * Test admin can view coupons list and create a new coupon.
     */
    public function test_admin_can_view_and_create_coupon(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.coupons.index'));
        $response->assertStatus(200);
        $response->assertSee('Coupons &amp; Promotions', false);
        $response->assertSee('Total Coupons');

        $createResponse = $this->actingAs($this->adminUser)->post(route('admin.coupons.store'), [
            'code'                => 'save20', // Should auto-uppercase
            'name'                => 'Save 20 Percent',
            'description'         => 'Test promo discount',
            'type'                => 'percentage',
            'value'               => 20.00,
            'min_order_amount'    => 50.00,
            'max_discount_amount' => 30.00,
            'usage_limit'         => 100,
            'usage_limit_per_user'=> 1,
            'is_active'           => 1,
        ]);

        $createResponse->assertRedirect(route('admin.coupons.index'));
        $createResponse->assertSessionHas('success');

        $coupon = Coupon::where('code', 'SAVE20')->where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($coupon);
        $this->assertEquals('SAVE20', $coupon->code);
        $this->assertEquals('percentage', $coupon->type);
        $this->assertEquals(20.00, $coupon->value);
        $this->assertEquals(50.00, $coupon->min_order_amount);
        $this->assertEquals(30.00, $coupon->max_discount_amount);
        $this->assertEquals(100, $coupon->usage_limit);
        $this->assertTrue($coupon->is_active);
    }

    /**
     * Test admin can edit, toggle status, and delete a coupon.
     */
    public function test_admin_can_edit_toggle_and_delete_coupon(): void
    {
        $coupon = Coupon::create([
            'tenant_id'            => $this->tenant->id,
            'code'                 => 'EDITME',
            'name'                 => 'Initial Name',
            'type'                 => 'fixed',
            'value'                => 10.00,
            'min_order_amount'     => 20.00,
            'is_active'            => true,
            'usage_limit_per_user' => 1,
        ]);

        // Edit
        $editResponse = $this->actingAs($this->adminUser)->get(route('admin.coupons.edit', $coupon->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('EDITME');

        $updateResponse = $this->actingAs($this->adminUser)->put(route('admin.coupons.update', $coupon->id), [
            'code'                => 'EDITME',
            'name'                => 'Updated Name',
            'type'                => 'fixed',
            'value'               => 15.00,
            'min_order_amount'    => 25.00,
            'is_active'           => 1,
        ]);
        $updateResponse->assertRedirect(route('admin.coupons.index'));

        $coupon->refresh();
        $this->assertEquals('Updated Name', $coupon->name);
        $this->assertEquals(15.00, $coupon->value);

        // Toggle Status
        $this->actingAs($this->adminUser)->post(route('admin.coupons.toggle', $coupon->id));
        $coupon->refresh();
        $this->assertFalse($coupon->is_active);

        // Delete
        $deleteResponse = $this->actingAs($this->adminUser)->delete(route('admin.coupons.destroy', $coupon->id));
        $deleteResponse->assertRedirect(route('admin.coupons.index'));
        $this->assertSoftDeleted('coupons', ['id' => $coupon->id]);
    }

    /**
     * Test percentage coupon with max discount cap.
     */
    public function test_percentage_coupon_with_max_discount_cap(): void
    {
        // 25% off with $20 cap
        $coupon = Coupon::create([
            'tenant_id'           => $this->tenant->id,
            'code'                => 'CAP20',
            'name'                => '25% Capped at $20',
            'type'                => 'percentage',
            'value'               => 25.00,
            'min_order_amount'    => 0.00,
            'max_discount_amount' => 20.00,
            'is_active'           => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 2); // 2 x $100 = $200 subtotal

        $cart->coupon_code = 'CAP20';
        $cart->save();

        $pricingEngine = app(PricingEngine::class);
        $calculation = $pricingEngine->calculate($cart);

        // Subtotal = $200. 25% of $200 = $50, but max cap is $20.
        $this->assertEquals(200.00, $calculation['subtotal']);
        $this->assertEquals(20.00, $calculation['discount_amount']);
        $this->assertEquals('CAP20', $calculation['coupon_code']);
    }

    /**
     * Test fixed amount coupon calculation.
     */
    public function test_fixed_amount_coupon_calculation(): void
    {
        $coupon = Coupon::create([
            'tenant_id'        => $this->tenant->id,
            'code'             => 'FLAT15',
            'name'             => '$15 Flat Off',
            'type'             => 'fixed',
            'value'            => 15.00,
            'min_order_amount' => 0.00,
            'is_active'        => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1); // 1 x $100 = $100 subtotal

        $cart->coupon_code = 'FLAT15';
        $cart->save();

        $pricingEngine = app(PricingEngine::class);
        $calculation = $pricingEngine->calculate($cart);

        $this->assertEquals(100.00, $calculation['subtotal']);
        $this->assertEquals(15.00, $calculation['discount_amount']);
        // Taxable base = $85, 10% tax = $8.50, grand total = $93.50
        $this->assertEquals(93.50, $calculation['grand_total']);
    }

    /**
     * Test coupon minimum spend condition rejects and accepts appropriately.
     */
    public function test_coupon_minimum_spend_validation(): void
    {
        $coupon = Coupon::create([
            'tenant_id'        => $this->tenant->id,
            'code'             => 'BIGSPEND',
            'name'             => 'Save $30 on $150+',
            'type'             => 'fixed',
            'value'            => 30.00,
            'min_order_amount' => 150.00,
            'is_active'        => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1); // Subtotal = $100

        $promotionService = app(PromotionService::class);

        // Subtotal $100 < $150 minimum -> should fail
        $validation = $promotionService->validateCoupon('BIGSPEND', $cart);
        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('minimum spend of $150.00', $validation['error']);

        // Increase quantity to 2 -> Subtotal = $200 >= $150 -> should succeed
        $cartService->addItem($this->variant->id, 1);
        $validationValid = $promotionService->validateCoupon('BIGSPEND', $cart);
        $this->assertTrue($validationValid['valid']);
        $this->assertEquals(30.00, $validationValid['discount']);
    }

    /**
     * Test coupon expiration and validity window.
     */
    public function test_coupon_expiration_and_validity_dates(): void
    {
        // Expired coupon
        Coupon::create([
            'tenant_id'  => $this->tenant->id,
            'code'       => 'PASTCOUPON',
            'name'       => 'Expired Promo',
            'type'       => 'percentage',
            'value'      => 10.00,
            'expires_at' => now()->subDay(),
            'is_active'  => true,
        ]);

        // Future coupon
        Coupon::create([
            'tenant_id'  => $this->tenant->id,
            'code'       => 'FUTURECOUPON',
            'name'       => 'Future Promo',
            'type'       => 'percentage',
            'value'      => 10.00,
            'starts_at'  => now()->addDays(5),
            'is_active'  => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1);

        $promotionService = app(PromotionService::class);

        $expiredResult = $promotionService->validateCoupon('PASTCOUPON', $cart);
        $this->assertFalse($expiredResult['valid']);
        $this->assertStringContainsString('expired', $expiredResult['error']);

        $futureResult = $promotionService->validateCoupon('FUTURECOUPON', $cart);
        $this->assertFalse($futureResult['valid']);
        $this->assertStringContainsString('not active yet', $futureResult['error']);
    }

    /**
     * Test per-customer and global usage limits.
     */
    public function test_coupon_usage_limits_per_user_and_global(): void
    {
        $coupon = Coupon::create([
            'tenant_id'            => $this->tenant->id,
            'code'                 => 'ONCEONLY',
            'name'                 => 'Once Per Customer',
            'type'                 => 'percentage',
            'value'                => 15.00,
            'usage_limit_per_user' => 1,
            'usage_limit'          => 5,
            'is_active'            => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1);

        $promotionService = app(PromotionService::class);

        // First attempt for customer john@example.com -> Valid
        $firstAttempt = $promotionService->validateCoupon('ONCEONLY', $cart, null, 'john@example.com');
        $this->assertTrue($firstAttempt['valid']);

        // Record usage for john@example.com
        $dummyOrder = Order::create([
            'tenant_id'          => $this->tenant->id,
            'store_id'           => $this->store->id,
            'tenant_branch_id'   => $this->branch->id,
            'order_number'       => 'ORD-DUMMY-1',
            'customer_name'      => 'John Doe',
            'customer_email'     => 'john@example.com',
            'subtotal'           => 100.00,
            'discount_amount'    => 15.00,
            'grand_total'        => 95.00,
            'currency'           => 'USD',
            'status'             => 'completed',
            'payment_status'     => 'paid',
        ]);

        $promotionService->recordUsage($coupon, $dummyOrder, null, 'john@example.com', 15.00);

        // Second attempt for john@example.com -> Must be rejected
        $secondAttempt = $promotionService->validateCoupon('ONCEONLY', $cart, null, 'john@example.com');
        $this->assertFalse($secondAttempt['valid']);
        $this->assertStringContainsString('maximum number of times', $secondAttempt['error']);

        // Attempt for another customer jane@example.com -> Must be valid
        $janeAttempt = $promotionService->validateCoupon('ONCEONLY', $cart, null, 'jane@example.com');
        $this->assertTrue($janeAttempt['valid']);
    }

    /**
     * Test checkout records coupon usage, attaches code to order, and increments times_used.
     */
    public function test_checkout_records_coupon_usage_and_increments_count(): void
    {
        $coupon = Coupon::create([
            'tenant_id'            => $this->tenant->id,
            'code'                 => 'CHECKOUT10',
            'name'                 => '10% Checkout Coupon',
            'type'                 => 'percentage',
            'value'                => 10.00,
            'usage_limit_per_user' => 1,
            'times_used'           => 0,
            'is_active'            => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1); // $100

        // Apply coupon
        $cart->coupon_code = 'CHECKOUT10';
        $cart->save();

        // Process checkout
        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->processCheckout(
            $cart,
            [
                'name'             => 'Coupon Shopper',
                'email'            => 'shopper@test.com',
                'phone'            => '1234567890',
                'shipping_address' => ['address' => '456 Market St', 'city' => 'Chicago'],
            ],
            'cod'
        );

        $this->assertNotNull($order);
        $this->assertEquals('CHECKOUT10', $order->coupon_code);
        $this->assertEquals(10.00, (float) $order->discount_amount);

        // Assert coupon times_used incremented
        $coupon->refresh();
        $this->assertEquals(1, $coupon->times_used);

        // Assert CouponUsage record created
        $usage = CouponUsage::where('order_id', $order->id)->where('coupon_id', $coupon->id)->first();
        $this->assertNotNull($usage);
        $this->assertEquals('shopper@test.com', $usage->customer_email);
        $this->assertEquals(10.00, $usage->discount_amount);
    }

    /**
     * Test Storefront Cart apply and remove coupon HTTP endpoints.
     */
    public function test_cart_coupon_apply_and_remove_endpoints(): void
    {
        Coupon::create([
            'tenant_id' => $this->tenant->id,
            'code'      => 'WEBPROMO',
            'name'      => 'Web Promo 10%',
            'type'      => 'percentage',
            'value'     => 10.00,
            'is_active' => true,
        ]);

        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1);

        // Apply via POST
        $applyResponse = $this->post(route('store.cart.coupon'), [
            'coupon_code' => 'WEBPROMO',
        ]);
        $applyResponse->assertRedirect(route('store.cart.index'));
        $applyResponse->assertSessionHas('success');

        $cart->refresh();
        $this->assertEquals('WEBPROMO', $cart->coupon_code);

        // Remove via DELETE
        $removeResponse = $this->delete(route('store.cart.coupon.remove'));
        $removeResponse->assertRedirect(route('store.cart.index'));
        $removeResponse->assertSessionHas('success');

        $cart->refresh();
        $this->assertNull($cart->coupon_code);
    }
}
