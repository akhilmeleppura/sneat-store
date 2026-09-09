<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Events\LowStockAlertEvent;
use Modules\Inventory\Services\InventoryService;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Models\VendorPayout;
use Modules\Order\Events\OrderPlacedEvent;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Notifications\OrderConfirmationNotification;
use Modules\Payment\Events\OrderPaymentSettledEvent;
use Modules\Payment\Services\FinancialSettlementService;
use Tests\TestCase;

class NotificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected User $adminUser;
    protected User $customerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Notification Test Tenant',
            'slug' => 'notify-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Notification Store',
            'slug'       => 'notify-store-' . Str::random(5),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Notification Branch',
            'slug'       => 'notify-branch-' . Str::random(5),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->adminUser = User::create([
            'name'              => 'Platform Supreme Admin',
            'email'             => 'admin.notify.' . Str::random(5) . '@sneat.test',
            'password'          => bcrypt('password'),
            'is_supreme_admin'  => true,
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
            'store_id'          => $this->store->id,
            'tenant_branch_id'  => $this->branch->id,
        ]);

        $this->customerUser = User::create([
            'name'              => 'Customer User',
            'email'             => 'customer.notify.' . Str::random(5) . '@sneat.test',
            'password'          => bcrypt('password'),
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
            'store_id'          => $this->store->id,
        ]);
    }

    /**
     * Test order placement dispatches OrderPlacedEvent and stores database notification.
     */
    public function test_order_placement_dispatches_event_and_notifies_customer(): void
    {
        $order = Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'user_id'          => $this->customerUser->id,
            'order_number'     => 'ORD-NOTIFY-' . Str::random(6),
            'customer_name'    => $this->customerUser->name,
            'customer_email'   => $this->customerUser->email,
            'status'           => 'pending',
            'subtotal'         => 120.00,
            'tax_amount'       => 12.00,
            'shipping_amount'  => 10.00,
            'grand_total'      => 142.00,
            'currency'         => 'USD',
            'shipping_address' => ['street' => '123 Test St'],
            'billing_address'  => ['street' => '123 Test St'],
        ]);

        // Trigger event directly
        event(new OrderPlacedEvent($order));

        // Refresh user and assert notification was stored in database
        $this->customerUser->refresh();
        $this->assertGreaterThan(0, $this->customerUser->notifications()->count());

        $notification = $this->customerUser->notifications()->first();
        $this->assertEquals('order_confirmation', $notification->data['type']);
        $this->assertEquals($order->order_number, $notification->data['order_number']);
    }

    /**
     * Test order settlement notifies vendor with net commission earnings.
     */
    public function test_order_settlement_notifies_vendor_with_net_earnings(): void
    {
        $vendorUser = User::create([
            'name'     => 'Tech Vendor Owner',
            'email'    => 'vendor.owner.' . Str::random(5) . '@sneat.test',
            'password' => bcrypt('password'),
        ]);

        $vendor = Vendor::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $vendorUser->id,
            'name'            => 'Tech Store Global',
            'slug'            => 'tech-store-' . Str::random(4),
            'email'           => $vendorUser->email,
            'commission_rate' => 15.00, // 15% platform fee
            'balance'         => 0.00,
            'status'          => 'active',
        ]);

        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'vendor_id' => $vendor->id,
            'name'      => 'Mechanical Keyboard',
            'slug'      => 'mech-kb-' . Str::random(4),
            'price'     => 100.00,
            'status'    => 'published',
        ]);

        $variant = ProductVariant::create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $product->id,
            'sku'        => 'KB-RGB-' . Str::random(4),
            'price'      => 100.00,
        ]);

        $order = Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'user_id'          => $this->customerUser->id,
            'order_number'     => 'ORD-VENDOR-' . Str::random(6),
            'customer_name'    => $this->customerUser->name,
            'customer_email'   => $this->customerUser->email,
            'status'           => 'pending',
            'payment_status'   => 'unpaid',
            'subtotal'         => 100.00,
            'tax_amount'       => 10.00,
            'shipping_amount'  => 5.00,
            'grand_total'      => 115.00,
            'currency'         => 'USD',
            'shipping_address' => ['street' => '123 Main'],
            'billing_address'  => ['street' => '123 Main'],
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'vendor_id'          => $vendor->id,
            'product_name'       => $product->name,
            'variant_sku'        => $variant->sku,
            'unit_price'         => 100.00,
            'quantity'           => 1,
            'tax_amount'         => 0.00,
            'discount_amount'    => 0.00,
            'line_total'         => 100.00,
        ]);

        // Run financial settlement
        $settlementService = app(FinancialSettlementService::class);
        $result = $settlementService->settleOrder($order, 'stripe', 'txn_' . Str::random(8));

        $this->assertEquals('settled', $result['status']);

        // Check vendor user received notification
        $vendorUser->refresh();
        $this->assertGreaterThan(0, $vendorUser->notifications()->count());

        $notification = $vendorUser->notifications()->first();
        $this->assertEquals('vendor_sale', $notification->data['type']);
        $this->assertEquals(85.00, (float) $notification->data['net_earnings']); // 100 - 15%
    }

    /**
     * Test low stock triggers alert event and notifies administrators.
     */
    public function test_low_stock_triggers_alert_and_notifies_admin(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Limited Edition Sneakers',
            'slug'      => 'limited-sneakers-' . Str::random(4),
            'price'     => 220.00,
            'status'    => 'published',
        ]);

        $variant = ProductVariant::create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $product->id,
            'sku'        => 'SNK-LTD-' . Str::random(4),
            'price'      => 220.00,
        ]);

        // Adjust stock down to 3 (below reorder level of 5)
        $invService = app(InventoryService::class);
        $invService->adjustStock($variant->id, $this->branch->id, 3, 'Restock minimal');

        $this->adminUser->refresh();
        $this->assertGreaterThan(0, $this->adminUser->notifications()->count());

        $notification = $this->adminUser->notifications()->first();
        $this->assertEquals('low_stock_warning', $notification->data['type']);
        $this->assertEquals($variant->sku, $notification->data['sku']);
    }

    /**
     * Test vendor payout request and approval notifications.
     */
    public function test_vendor_payout_request_and_approval_notifications(): void
    {
        $vendorUser = User::create([
            'name'     => 'Payout Vendor Owner',
            'email'    => 'payout.vendor.' . Str::random(5) . '@sneat.test',
            'password' => bcrypt('password'),
        ]);

        $vendor = Vendor::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $vendorUser->id,
            'name'            => 'Fast Express Vendor',
            'slug'            => 'fast-express-' . Str::random(4),
            'email'           => $vendorUser->email,
            'commission_rate' => 10.00,
            'balance'         => 500.00,
            'status'          => 'active',
        ]);

        // 1. Vendor requests payout
        $response = $this->actingAs($vendorUser)->post('/vendor/payouts', [
            'amount'        => 150.00,
            'payout_method' => 'bank_transfer',
            'notes'         => 'Bi-weekly vendor earnings withdrawal',
        ]);

        $response->assertRedirect('/vendor/payouts');

        $vendorUser->refresh();
        $this->assertGreaterThan(0, $vendorUser->notifications()->count());
        $payoutNotice = $vendorUser->notifications()->first();
        $this->assertEquals('payout_status', $payoutNotice->data['type']);
        $this->assertEquals('pending', $payoutNotice->data['status']);

        // 2. Admin approves payout
        $payout = VendorPayout::where('vendor_id', $vendor->id)->first();
        $this->assertNotNull($payout);

        $approveResponse = $this->actingAs($this->adminUser)->post("/admin/marketplace/payouts/{$payout->id}/action", [
            'action'                => 'approve',
            'transaction_reference' => 'WIRE-REF-998877',
        ]);

        $approveResponse->assertSessionHas('success');

        $vendorUser->refresh();
        $this->assertEquals(2, $vendorUser->notifications()->count());
        $approvedNotice = $vendorUser->notifications()->where('data->status', 'approved')->first();
        $this->assertNotNull($approvedNotice);
        $this->assertEquals('payout_status', $approvedNotice->data['type']);
        $this->assertEquals('approved', $approvedNotice->data['status']);
    }

    /**
     * Test notification feed API and mark-all-as-read endpoint.
     */
    public function test_notification_feed_api_and_mark_all_read(): void
    {
        // Place 2 orders for customer to generate notifications
        for ($i = 1; $i <= 2; $i++) {
            $order = Order::create([
                'tenant_id'        => $this->tenant->id,
                'store_id'         => $this->store->id,
                'user_id'          => $this->customerUser->id,
                'order_number'     => 'ORD-FEED-' . $i . '-' . Str::random(4),
                'customer_name'    => $this->customerUser->name,
                'customer_email'   => $this->customerUser->email,
                'status'           => 'pending',
                'subtotal'         => 50.00 * $i,
                'tax_amount'       => 5.00 * $i,
                'shipping_amount'  => 0.00,
                'grand_total'      => 55.00 * $i,
                'currency'         => 'USD',
                'shipping_address' => ['street' => 'Street ' . $i],
                'billing_address'  => ['street' => 'Street ' . $i],
            ]);

            event(new OrderPlacedEvent($order));
        }

        // Test feed endpoint
        $feedResponse = $this->actingAs($this->customerUser)->getJson('/notifications/feed');
        $feedResponse->assertStatus(200);
        $feedResponse->assertJsonStructure([
            'unread_count',
            'notifications',
        ]);
        $this->assertEquals(2, $feedResponse->json('unread_count'));

        // Test mark all read
        $markAllResponse = $this->actingAs($this->customerUser)->postJson('/notifications/mark-all-read');
        $markAllResponse->assertStatus(200);
        $this->assertEquals(0, $markAllResponse->json('unread_count'));

        $this->customerUser->refresh();
        $this->assertEquals(0, $this->customerUser->unreadNotifications()->count());

        // Test notification center UI pages
        $customerCenter = $this->actingAs($this->customerUser)->get('/account/notifications');
        $customerCenter->assertStatus(200);
        $customerCenter->assertSee('My Notifications');

        $adminCenter = $this->actingAs($this->adminUser)->get('/admin/notifications');
        $adminCenter->assertStatus(200);
        $adminCenter->assertSee('System Notifications');
    }
}
