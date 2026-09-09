<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
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
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;
use Modules\Order\Models\Shipment;
use Modules\Order\Models\ShippingMethod;
use Modules\Order\Services\CheckoutService;
use Modules\Order\Services\PricingEngine;
use Modules\Order\Services\ShippingService;
use Tests\TestCase;

class ShippingFulfillmentTest extends TestCase
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
            'name' => 'Logistics Tenant',
            'slug' => 'logistics-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Logistics Store',
            'slug'       => 'logistics-store-' . Str::random(5),
            'code'       => 'STR-' . Str::random(3),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Logistics Warehouse',
            'slug'       => 'logistics-branch-' . Str::random(5),
            'code'       => 'BR-' . Str::random(3),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->adminUser = User::create([
            'name'              => 'Logistics Manager',
            'email'             => 'admin.logistics.' . Str::random(5) . '@sneat.test',
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
            'name'        => 'Gaming Laptop',
            'slug'        => 'gaming-laptop-' . Str::random(5),
            'price'       => 500.00,
            'is_active'   => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'sku'        => 'LAPTOP-PRO-' . Str::random(4),
            'price'      => 500.00,
        ]);

        // Stock intake: 25 units
        app(InventoryService::class)->adjustStock(
            $this->variant->id,
            $this->branch->id,
            25,
            'initial',
            'init',
            null,
            'Initial warehouse intake'
        );
    }

    /**
     * Test ShippingMethod model calculations for flat, free threshold, weight tiered, and total tiered.
     */
    public function test_shipping_method_rate_calculation_modes(): void
    {
        // 1. Flat Rate Method
        $flat = ShippingMethod::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Standard Ground',
            'code'      => 'STANDARD_GROUND',
            'carrier'   => 'FedEx',
            'rate_type' => 'flat',
            'base_rate' => 15.00,
            'min_days'  => 3,
            'max_days'  => 5,
            'is_active' => true,
        ]);

        $this->assertEquals(15.00, $flat->calculateRate(subtotal: 50.0, weight: 2.0));
        $this->assertEquals('3 - 5 business days', $flat->estimated_delivery_text);

        // 2. Free Threshold Method ($100+ gets free shipping)
        $threshold = ShippingMethod::create([
            'tenant_id'               => $this->tenant->id,
            'name'                    => 'Economy Ground',
            'code'                    => 'ECONOMY_GROUND',
            'carrier'                 => 'UPS',
            'rate_type'               => 'flat',
            'base_rate'               => 10.00,
            'free_shipping_threshold' => 100.00,
            'min_days'                => 4,
            'max_days'                => 7,
            'is_active'               => true,
        ]);

        $this->assertEquals(10.00, $threshold->calculateRate(subtotal: 80.0));
        $this->assertEquals(0.00, $threshold->calculateRate(subtotal: 100.00));
        $this->assertEquals(0.00, $threshold->calculateRate(subtotal: 250.00));

        // 3. Weight Tiered Method
        $weightTiered = ShippingMethod::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Heavy Freight',
            'code'      => 'FREIGHT',
            'carrier'   => 'DHL',
            'rate_type' => 'tiered_weight',
            'base_rate' => 20.00,
            'settings'  => [
                'weight_tiers' => [
                    ['max_weight' => 2.0, 'rate' => 8.00],
                    ['max_weight' => 10.0, 'rate' => 18.00],
                    ['max_weight' => 30.0, 'rate' => 45.00],
                ]
            ],
            'min_days'  => 2,
            'max_days'  => 4,
            'is_active' => true,
        ]);

        $this->assertEquals(8.00, $weightTiered->calculateRate(subtotal: 50.0, weight: 1.5));
        $this->assertEquals(18.00, $weightTiered->calculateRate(subtotal: 50.0, weight: 8.0));
        $this->assertEquals(45.00, $weightTiered->calculateRate(subtotal: 50.0, weight: 25.0));

        // 4. Order Total Tiered Method
        $totalTiered = ShippingMethod::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Subtotal Promo Carrier',
            'code'      => 'SUBTOTAL_TIERED',
            'carrier'   => 'USPS',
            'rate_type' => 'tiered_total',
            'base_rate' => 25.00,
            'settings'  => [
                'total_tiers' => [
                    ['min_total' => 200.0, 'rate' => 0.00],
                    ['min_total' => 100.0, 'rate' => 5.00],
                    ['min_total' => 0.0, 'rate' => 12.00],
                ]
            ],
            'min_days'  => 1,
            'max_days'  => 2,
            'is_active' => true,
        ]);

        $this->assertEquals(12.00, $totalTiered->calculateRate(subtotal: 40.0));
        $this->assertEquals(5.00, $totalTiered->calculateRate(subtotal: 120.0));
        $this->assertEquals(0.00, $totalTiered->calculateRate(subtotal: 250.0));
    }

    /**
     * Test PricingEngine calculates exact carrier rates when shippingMethodId is supplied.
     */
    public function test_pricing_engine_uses_selected_shipping_method(): void
    {
        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1); // 1 laptop = $500

        $expressMethod = ShippingMethod::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Express Courier Next Day',
            'code'      => 'EXPRESS_NEXT_DAY',
            'carrier'   => 'FedEx',
            'rate_type' => 'flat',
            'base_rate' => 35.00,
            'min_days'  => 1,
            'max_days'  => 1,
            'is_active' => true,
        ]);

        $pricingEngine = app(PricingEngine::class);

        $calculation = $pricingEngine->calculate($cart, $expressMethod->id);

        $this->assertEquals(500.00, $calculation['subtotal']);
        $this->assertEquals(35.00, $calculation['shipping_amount']);
        $this->assertEquals($expressMethod->id, $calculation['shipping_method_id']);
        // Subtotal (500) + Tax 10% (50) + Shipping (35) = 585
        $this->assertEquals(585.00, $calculation['grand_total']);
    }

    /**
     * Test full checkout flow: stores shipping method on Order and creates initial pending Shipment.
     */
    public function test_checkout_workflow_persists_shipping_method_and_creates_shipment(): void
    {
        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 2); // 2 laptops = $1000

        $shippingMethod = ShippingMethod::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'FedEx Ground Delivery',
            'code'      => 'FEDEX_GROUND',
            'carrier'   => 'FedEx',
            'rate_type' => 'flat',
            'base_rate' => 20.00,
            'min_days'  => 2,
            'max_days'  => 5,
            'is_active' => true,
        ]);

        $checkoutService = app(CheckoutService::class);

        $order = $checkoutService->processCheckout(
            $cart,
            [
                'name'  => 'John Miller',
                'email' => 'john.miller@example.com',
                'phone' => '+1 555-432-1987',
                'shipping_address' => [
                    'street'      => '100 Innovation Blvd',
                    'city'        => 'Austin',
                    'state'       => 'TX',
                    'postal_code' => '78701',
                    'country'     => 'United States',
                ],
            ],
            'cod',
            'Leave with reception',
            $shippingMethod->id
        );

        $this->assertNotNull($order->id);
        $this->assertEquals($shippingMethod->id, $order->shipping_method_id);
        $this->assertNotNull($order->estimated_delivery_date);

        // Verify initial Shipment fulfillment record created
        $order->load('shipments');
        $this->assertCount(1, $order->shipments);

        $shipment = $order->shipments->first();
        $this->assertEquals($this->tenant->id, $shipment->tenant_id);
        $this->assertEquals($order->id, $shipment->order_id);
        $this->assertEquals($shippingMethod->id, $shipment->shipping_method_id);
        $this->assertEquals(Shipment::STATUS_PENDING, $shipment->status);
        $this->assertStringStartsWith('SHP-', $shipment->shipment_number);
        $this->assertEquals('John Miller', $shipment->recipient_name);
        $this->assertNotEmpty($shipment->timeline);
    }

    /**
     * Test shipment fulfillment lifecycle: dispatch, carrier tracking, in transit, delivered.
     */
    public function test_shipment_fulfillment_dispatch_and_delivery_lifecycle(): void
    {
        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1);

        $shippingMethod = ShippingMethod::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'DHL Express Express',
            'code'      => 'DHL_EXP',
            'carrier'   => 'DHL',
            'rate_type' => 'flat',
            'base_rate' => 25.00,
            'min_days'  => 1,
            'max_days'  => 3,
            'is_active' => true,
        ]);

        $checkoutService = app(CheckoutService::class);
        $order = $checkoutService->processCheckout(
            $cart,
            [
                'name'  => 'Alice Walker',
                'email' => 'alice@example.com',
                'phone' => '555-888-9999',
                'shipping_address' => [
                    'street'      => '500 Technology Way',
                    'city'        => 'San Jose',
                    'state'       => 'CA',
                    'postal_code' => '95110',
                    'country'     => 'United States',
                ],
            ],
            'stripe',
            null,
            $shippingMethod->id
        );

        $shipment = $order->shipments()->first();
        $this->assertNotNull($shipment);

        $shippingService = app(ShippingService::class);

        // 1. Dispatch shipment with tracking code
        $dispatchedShipment = $shippingService->updateShipmentStatus(
            $shipment,
            Shipment::STATUS_DISPATCHED,
            'Picked up by DHL courier.',
            'Austin Hub',
            'DHL-987654321',
            'DHL Express',
            'https://dhl.com/track/987654321'
        );

        $this->assertEquals(Shipment::STATUS_DISPATCHED, $dispatchedShipment->status);
        $this->assertEquals('DHL-987654321', $dispatchedShipment->tracking_number);
        $this->assertNotNull($dispatchedShipment->shipped_at);

        // Parent Order should now be fulfilled
        $order->refresh();
        $this->assertEquals('fulfilled', $order->fulfillment_status);

        // 2. Advance to In Transit milestone
        $inTransitShipment = $shippingService->updateShipmentStatus(
            $dispatchedShipment,
            Shipment::STATUS_IN_TRANSIT,
            'Arrived at Dallas Sort Facility.',
            'Dallas, TX'
        );

        $this->assertEquals(Shipment::STATUS_IN_TRANSIT, $inTransitShipment->status);
        $this->assertCount(3, $inTransitShipment->timeline); // initial + dispatched + in_transit

        // 3. Complete Delivery
        $deliveredShipment = $shippingService->updateShipmentStatus(
            $inTransitShipment,
            Shipment::STATUS_DELIVERED,
            'Delivered directly to recipient front desk.',
            'San Jose, CA'
        );

        $this->assertEquals(Shipment::STATUS_DELIVERED, $deliveredShipment->status);
        $this->assertNotNull($deliveredShipment->delivered_at);

        $order->refresh();
        $this->assertEquals('fulfilled', $order->fulfillment_status);
        $this->assertEquals('completed', $order->status);
    }

    /**
     * Test admin CRUD and toggle actions for shipping methods.
     */
    public function test_admin_shipping_method_crud_and_toggle(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create method via HTTP POST
        $postResponse = $this->post(route('admin.shipping-methods.store'), [
            'name'                    => 'USPS Priority Mail',
            'code'                    => 'USPS_PRIORITY',
            'carrier'                 => 'USPS',
            'rate_type'               => 'flat',
            'base_rate'               => 9.50,
            'free_shipping_threshold' => 75.00,
            'min_days'                => 2,
            'max_days'                => 3,
            'description'             => 'Fast 2-3 day domestic shipping',
            'sort_order'              => 1,
            'is_active'               => 1,
        ]);

        $postResponse->assertRedirect(route('admin.shipping-methods.index'));
        $this->assertDatabaseHas('shipping_methods', [
            'code'      => 'USPS_PRIORITY',
            'tenant_id' => $this->tenant->id,
            'carrier'   => 'USPS',
        ]);

        $method = ShippingMethod::where('code', 'USPS_PRIORITY')->first();

        // 2. Update method via HTTP PUT
        $putResponse = $this->put(route('admin.shipping-methods.update', $method->id), [
            'name'                    => 'USPS Priority Mail Express',
            'code'                    => 'USPS_PRIORITY',
            'carrier'                 => 'USPS',
            'rate_type'               => 'flat',
            'base_rate'               => 14.00,
            'free_shipping_threshold' => 100.00,
            'min_days'                => 1,
            'max_days'                => 2,
            'description'             => 'Guaranteed 1-2 day service',
            'sort_order'              => 1,
            'is_active'               => 1,
        ]);

        $putResponse->assertRedirect(route('admin.shipping-methods.index'));
        $method->refresh();
        $this->assertEquals('USPS Priority Mail Express', $method->name);
        $this->assertEquals(14.00, (float) $method->base_rate);

        // 3. Toggle method status
        $toggleResponse = $this->post(route('admin.shipping-methods.toggle', $method->id));
        $toggleResponse->assertRedirect();
        $method->refresh();
        $this->assertFalse($method->is_active);

        // 4. Delete method
        $deleteResponse = $this->delete(route('admin.shipping-methods.destroy', $method->id));
        $deleteResponse->assertRedirect(route('admin.shipping-methods.index'));
        $this->assertSoftDeleted('shipping_methods', ['id' => $method->id]);
    }

    /**
     * Test admin shipment tracking views and milestone update routes.
     */
    public function test_admin_shipments_tracking_and_status_update(): void
    {
        $this->actingAs($this->adminUser);

        // Create an order and shipment
        $order = Order::create([
            'tenant_id'          => $this->tenant->id,
            'store_id'           => $this->store->id,
            'tenant_branch_id'   => $this->branch->id,
            'user_id'            => $this->adminUser->id,
            'order_number'       => 'ORD-' . date('Ymd') . '-TEST1',
            'customer_name'      => 'Robert Johnson',
            'customer_email'     => 'robert@test.com',
            'shipping_address'   => ['city' => 'Chicago', 'country' => 'USA'],
            'subtotal'           => 200.00,
            'discount_amount'    => 0.00,
            'tax_amount'         => 20.00,
            'shipping_amount'    => 10.00,
            'grand_total'        => 230.00,
            'currency'           => 'USD',
            'exchange_rate'      => 1.0,
            'status'             => 'pending',
            'payment_status'     => 'paid',
            'payment_method'     => 'stripe',
            'fulfillment_status' => 'unfulfilled',
        ]);

        $shipment = app(ShippingService::class)->createShipment($order, [
            'carrier' => 'FedEx',
        ]);

        // 1. Admin Index View
        $indexResponse = $this->get(route('admin.shipments.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($shipment->shipment_number);

        // 2. Admin Show View
        $showResponse = $this->get(route('admin.shipments.show', $shipment->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee($shipment->shipment_number);
        $showResponse->assertSee('Robert Johnson');

        // 3. Admin Dispatch Action
        $dispatchResponse = $this->post(route('admin.shipments.dispatch', $shipment->id), [
            'carrier'         => 'FedEx Freight',
            'tracking_number' => 'FX-888999000',
            'tracking_url'    => 'https://fedex.com/track/888999000',
        ]);
        $dispatchResponse->assertRedirect();

        $shipment->refresh();
        $this->assertEquals(Shipment::STATUS_DISPATCHED, $shipment->status);
        $this->assertEquals('FX-888999000', $shipment->tracking_number);

        // 4. Admin Update Milestone Action
        $milestoneResponse = $this->post(route('admin.shipments.status', $shipment->id), [
            'status'      => 'in_transit',
            'description' => 'Sorted at Chicago International Hub',
            'location'    => 'Chicago Hub',
        ]);
        $milestoneResponse->assertRedirect();

        $shipment->refresh();
        $this->assertEquals('in_transit', $shipment->status);
    }

    /**
     * Test checkout AJAX shipping recalculation endpoint.
     */
    public function test_checkout_ajax_calculate_shipping_endpoint(): void
    {
        $cartService = app(CartService::class);
        $cart = $cartService->getActiveCart();
        $cartService->addItem($this->variant->id, 1); // $500

        $express = ShippingMethod::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Overnight Air',
            'code'      => 'OVERNIGHT_AIR',
            'carrier'   => 'UPS',
            'rate_type' => 'flat',
            'base_rate' => 40.00,
            'min_days'  => 1,
            'max_days'  => 1,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('store.checkout.calculate_shipping'), [
            'shipping_method_id' => $express->id,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'pricing' => [
                'subtotal'           => 500.00,
                'shipping_amount'    => 40.00,
                'shipping_method_id' => $express->id,
                'grand_total'        => 590.00,
            ],
        ]);
    }
}
