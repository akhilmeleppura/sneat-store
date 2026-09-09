<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Cart\Services\CartService;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Models\InventoryStock;
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;
use Modules\Order\Services\CheckoutService;
use Modules\Order\Services\PricingEngine;
use Tests\TestCase;

class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'E-Commerce Test Tenant',
            'slug' => 'test-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Test Store',
            'slug'       => 'test-store-' . Str::random(5),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Main Branch',
            'slug'       => 'main-branch-' . Str::random(5),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->product = Product::create([
            'name'  => 'Premium Wireless Earbuds',
            'slug'  => 'earbuds-' . Str::random(5),
            'price' => 50.00,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'EARBUDS-BLK-' . Str::random(4),
            'price'      => 50.00,
        ]);

        // Add 20 units of physical stock
        app(InventoryService::class)->adjustStock(
            $this->variant->id,
            $this->branch->id,
            20,
            'initial',
            'init',
            null,
            'Initial intake'
        );
    }

    protected function tearDown(): void
    {
        Context::clear();
        InventoryStock::where('product_variant_id', $this->variant->id)->delete();
        $this->variant->forceDelete();
        $this->product->forceDelete();
        $this->branch->forceDelete();
        $this->store->forceDelete();
        $this->tenant->forceDelete();
        parent::tearDown();
    }

    public function test_add_item_to_cart_and_validate_stock_limits(): void
    {
        $cartService = app(CartService::class);

        // Add 2 units
        $item = $cartService->addItem($this->variant->id, 2);
        $this->assertEquals(2, $item->quantity);
        $this->assertEquals(100.00, $item->line_total);

        // Attempting to add more than remaining available stock (18 remaining) should throw exception
        $this->expectException(\Exception::class);
        $cartService->addItem($this->variant->id, 25);
    }

    public function test_pricing_engine_calculates_taxes_and_discounts(): void
    {
        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 2); // 2 * $50 = $100

        // Apply promo code WELCOME10 (10% discount)
        $cart->coupon_code = 'WELCOME10';
        $cart->save();

        $pricingEngine = app(PricingEngine::class);
        $calculation = $pricingEngine->calculate($cart);

        $this->assertEquals(100.00, $calculation['subtotal']);
        $this->assertEquals(10.00, $calculation['discount_amount']); // 10% of 100
        $this->assertEquals(9.00, $calculation['tax_amount']); // 10% tax on 90 taxable base
        $this->assertEquals(0.00, $calculation['shipping_amount']); // Free shipping threshold >= 100
        $this->assertEquals(99.00, $calculation['grand_total']); // 90 + 9
    }

    public function test_checkout_workflow_reserves_stock_and_creates_order(): void
    {
        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 3); // 3 units

        $checkoutService = app(CheckoutService::class);

        $order = $checkoutService->processCheckout(
            $cart,
            [
                'name'  => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone' => '555-123-4567',
                'shipping_address' => [
                    'street'      => '742 Evergreen Terrace',
                    'city'        => 'Springfield',
                    'state'       => 'IL',
                    'postal_code' => '62704',
                    'country'     => 'United States',
                ],
            ],
            'cod',
            'Please leave at porch'
        );

        // Verify order attributes
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('Jane Doe', $order->customer_name);
        $this->assertEquals(150.00, $order->subtotal);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals('unpaid', $order->payment_status);
        $this->assertEquals('unfulfilled', $order->fulfillment_status);

        // Verify physical stock reservation (20 on hand, 3 reserved, 17 available)
        $stock = InventoryStock::where('product_variant_id', $this->variant->id)
            ->where('tenant_branch_id', $this->branch->id)
            ->first();

        $this->assertEquals(20, $stock->quantity_on_hand);
        $this->assertEquals(3, $stock->quantity_reserved);
        $this->assertEquals(17, $stock->quantity_available);

        // Verify cart is now empty
        $cart->refresh();
        $this->assertTrue($cart->is_empty);

        // Clean up
        $order->items()->delete();
        $order->forceDelete();
    }
}
