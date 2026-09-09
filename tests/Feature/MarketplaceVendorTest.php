<?php

namespace Tests\Feature;

use App\Models\User;
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
use Modules\Marketplace\Models\VendorEarning;
use Modules\Marketplace\Models\VendorPayout;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Payment\Services\FinancialSettlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceVendorTest extends TestCase
{
    use RefreshDatabase;
    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Marketplace Test Tenant',
            'slug' => 'market-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Marketplace Store',
            'slug'       => 'market-store-' . Str::random(5),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Marketplace Hub',
            'slug'       => 'market-br-' . Str::random(5),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin-marketplace@sneat.test'],
            [
                'name'              => 'Marketplace Admin',
                'password'          => bcrypt('password'),
                'is_supreme_admin'  => true,
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Helper to create a vendor with an associated user.
     */
    protected function createVendor(string $name, float $commissionRate = 10.00, float $balance = 0.00): Vendor
    {
        $user = User::create([
            'name'              => $name . ' Owner',
            'email'             => Str::slug($name) . '@vendor.test',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        return Vendor::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $user->id,
            'name'            => $name,
            'slug'            => Str::slug($name) . '-' . Str::random(4),
            'email'           => $user->email,
            'commission_rate' => $commissionRate,
            'balance'         => $balance,
            'status'          => 'active',
        ]);
    }

    /**
     * Helper to create product for a vendor.
     */
    protected function createVendorProduct(Vendor $vendor, string $name, float $price): Product
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $vendor->id,
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . Str::random(4),
            'sku'       => 'SKU-' . strtoupper(Str::random(6)),
            'price'     => $price,
            'status'    => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id'     => $product->id,
            'sku'            => $product->sku . '-V1',
            'name'           => 'Standard',
            'price'          => $price,
            'stock_quantity' => 20,
            'manage_stock'   => true,
        ]);

        app(InventoryService::class)->adjustStock(
            $variant->id,
            $this->branch->id,
            20,
            'initial',
            'init',
            null,
            'Vendor product intake'
        );

        return $product;
    }

    /**
     * Test vendor creation and model attributes.
     */
    public function test_vendor_registration_and_activation(): void
    {
        $vendor = $this->createVendor('Apex Audio Gear', 12.50, 150.00);

        $this->assertEquals('Apex Audio Gear', $vendor->name);
        $this->assertEquals(12.50, (float) $vendor->commission_rate);
        $this->assertEquals(150.00, (float) $vendor->balance);
        $this->assertEquals('active', $vendor->status);
        $this->assertStringContainsString('Active', $vendor->status_badge);
    }

    /**
     * Test vendor catalog scoping in vendor portal.
     */
    public function test_vendor_product_catalog_scoping(): void
    {
        $vendorA = $this->createVendor('Vendor Alpha');
        $vendorB = $this->createVendor('Vendor Beta');

        $prodA = $this->createVendorProduct($vendorA, 'Alpha Noise Cancelling Headphones', 199.99);
        $prodB = $this->createVendorProduct($vendorB, 'Beta Mechanical Keyboard', 149.99);

        // Visit Vendor Alpha's product portal
        $response = $this->actingAs($vendorA->user)
            ->withHeaders(['X-Tenant-Key' => $this->tenant->slug])
            ->get(route('vendor.products.index'));

        $response->assertStatus(200);
        $response->assertSee('Alpha Noise Cancelling Headphones');
        $response->assertDontSee('Beta Mechanical Keyboard');
    }

    /**
     * Test multi-vendor revenue splitting and commission calculation upon settlement.
     */
    public function test_multi_vendor_order_settlement_splits_commissions(): void
    {
        $vendorA = $this->createVendor('Vendor Studio', 10.00, 0.00); // 10% fee
        $vendorB = $this->createVendor('Vendor Tech', 20.00, 0.00);   // 20% fee

        $prodA = $this->createVendorProduct($vendorA, 'Studio Mic', 100.00);
        $prodB = $this->createVendorProduct($vendorB, 'Tech Webcam', 200.00);

        // Create multi-vendor customer order
        $order = Order::create([
            'tenant_id'          => $this->tenant->id,
            'store_id'           => $this->store->id,
            'tenant_branch_id'   => $this->branch->id,
            'user_id'            => $this->adminUser->id,
            'order_number'       => 'ORD-MV-' . rand(10000, 99999),
            'customer_name'      => 'Robert Miller',
            'customer_email'     => 'robert.miller@test.com',
            'subtotal'           => 300.00,
            'discount_amount'    => 0.00,
            'tax_amount'         => 0.00,
            'shipping_amount'    => 0.00,
            'grand_total'        => 300.00,
            'currency'           => 'USD',
            'status'             => 'pending',
            'payment_status'     => 'unpaid',
            'payment_method'     => 'mock',
            'fulfillment_status' => 'unfulfilled',
        ]);

        $itemA = OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $prodA->id,
            'product_variant_id' => $prodA->variants->first()->id,
            'product_name'       => $prodA->name,
            'variant_sku'        => $prodA->sku,
            'unit_price'         => 100.00,
            'quantity'           => 1,
            'tax_amount'         => 0.00,
            'discount_amount'    => 0.00,
            'line_total'         => 100.00,
            'vendor_id'          => $vendorA->id,
        ]);

        $itemB = OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $prodB->id,
            'product_variant_id' => $prodB->variants->first()->id,
            'product_name'       => $prodB->name,
            'variant_sku'        => $prodB->sku,
            'unit_price'         => 200.00,
            'quantity'           => 1,
            'tax_amount'         => 0.00,
            'discount_amount'    => 0.00,
            'line_total'         => 200.00,
            'vendor_id'          => $vendorB->id,
        ]);

        // Settle order
        $settlementService = app(FinancialSettlementService::class);
        $settlementService->settleOrder($order, 'mv_txn_123');

        // Check Vendor A: 10% fee on $100 = $10 fee, $90 net earnings
        $vendorA->refresh();
        $this->assertEquals(90.00, (float) $vendorA->balance);

        $earningA = VendorEarning::where('order_item_id', $itemA->id)->first();
        $this->assertNotNull($earningA);
        $this->assertEquals(10.00, (float) $earningA->commission_amount);
        $this->assertEquals(90.00, (float) $earningA->net_amount);

        // Check Vendor B: 20% fee on $200 = $40 fee, $160 net earnings
        $vendorB->refresh();
        $this->assertEquals(160.00, (float) $vendorB->balance);

        $earningB = VendorEarning::where('order_item_id', $itemB->id)->first();
        $this->assertNotNull($earningB);
        $this->assertEquals(40.00, (float) $earningB->commission_amount);
        $this->assertEquals(160.00, (float) $earningB->net_amount);
    }

    /**
     * Test vendor payout request and admin review approval.
     */
    public function test_vendor_payout_request_and_admin_approval(): void
    {
        $vendor = $this->createVendor('Audio Masters', 10.00, 500.00);

        // 1. Vendor requests payout of $200
        $requestResponse = $this->actingAs($vendor->user)
            ->withHeaders(['X-Tenant-Key' => $this->tenant->slug])
            ->post(route('vendor.payouts.store'), [
                'amount'        => 200.00,
                'payout_method' => 'bank_transfer',
                'notes'         => 'Routing 123456789, Acct 987654321',
            ]);

        $requestResponse->assertRedirect(route('vendor.payouts.index'));

        $payout = VendorPayout::where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($payout);
        $this->assertEquals(200.00, (float) $payout->amount);
        $this->assertEquals('requested', $payout->status);

        // 2. Platform Admin approves payout
        $adminActionResponse = $this->actingAs($this->adminUser)
            ->withHeaders(['X-Tenant-Key' => $this->tenant->slug])
            ->post(route('admin.marketplace.payouts.action', $payout->id), [
                'action'                => 'approve',
                'transaction_reference' => 'WIRE-998877',
                'notes'                 => 'Wire transferred via Chase Corporate Bank',
            ]);

        $adminActionResponse->assertRedirect();

        $payout->refresh();
        $this->assertEquals('completed', $payout->status);
        $this->assertEquals('WIRE-998877', $payout->transaction_reference);

        // Vendor balance decremented from $500 -> $300
        $vendor->refresh();
        $this->assertEquals(300.00, (float) $vendor->balance);
    }

    /**
     * Test access authorization to vendor portal.
     */
    public function test_vendor_portal_access_authorization(): void
    {
        $nonVendorUser = User::create([
            'name'              => 'Normal Customer',
            'email'             => 'customer@sneat.test',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Customer without vendor account gets 403
        $response = $this->actingAs($nonVendorUser)
            ->withHeaders(['X-Tenant-Key' => $this->tenant->slug])
            ->get(route('vendor.dashboard'));

        $response->assertStatus(403);

        // Active vendor gets 200 OK
        $vendor = $this->createVendor('Verified Vendor');
        $vendorResponse = $this->actingAs($vendor->user)
            ->withHeaders(['X-Tenant-Key' => $this->tenant->slug])
            ->get(route('vendor.dashboard'));

        $vendorResponse->assertStatus(200);
        $vendorResponse->assertSee($vendor->name);
    }
}
