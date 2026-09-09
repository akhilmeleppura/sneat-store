<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Models\InventoryStock;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Models\VendorEarning;
use Modules\Marketplace\Models\VendorPayout;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Services\AnalyticsService;
use Modules\Marketplace\Services\VendorAnalyticsService;
use Modules\Inventory\Services\InventoryReportService;
use Tests\TestCase;

class AnalyticsReportingTest extends TestCase
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
            'name' => 'Analytics Test Tenant',
            'slug' => 'analytics-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Analytics Store',
            'slug'       => 'analytics-store-' . Str::random(5),
            'code'       => 'STORE-ANL-' . Str::random(3),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Analytics Branch',
            'slug'       => 'analytics-branch-' . Str::random(5),
            'code'       => 'BR-ANL-' . Str::random(3),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $this->adminUser = User::create([
            'name'              => 'Analytics Admin',
            'email'             => 'admin.analytics.' . Str::random(5) . '@sneat.test',
            'password'          => bcrypt('password'),
            'is_supreme_admin'  => true,
            'email_verified_at' => now(),
            'tenant_id'         => $this->tenant->id,
            'store_id'          => $this->store->id,
            'tenant_branch_id'  => $this->branch->id,
        ]);
    }

    /**
     * Test admin sales report page renders successfully with 200 OK.
     */
    public function test_admin_sales_report_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.reports.sales', ['period' => '30_days']));

        $response->assertStatus(200);
        $response->assertSee('Sales Analytics');
        $response->assertSee('Gross Sales');
        $response->assertSee('Net Revenue');
        $response->assertSee('Total Orders');
        $response->assertSee('Avg. Order Value (AOV)');
    }

    /**
     * Test sales analytics service calculates correct sales KPIs.
     */
    public function test_sales_analytics_service_calculates_correct_kpis(): void
    {
        $prefix = Str::random(6);

        // Create 2 completed orders and 1 cancelled order
        Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'tenant_branch_id' => $this->branch->id,
            'order_number'     => 'ORD-' . $prefix . '-001',
            'customer_name'    => 'John Doe',
            'customer_email'   => 'john@example.com',
            'subtotal'         => 100.00,
            'discount_amount'  => 10.00,
            'tax_amount'       => 5.00,
            'shipping_amount'  => 15.00,
            'grand_total'      => 110.00,
            'status'           => 'completed',
            'payment_status'   => 'paid',
            'created_at'       => now()->subDays(2),
        ]);

        Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'tenant_branch_id' => $this->branch->id,
            'order_number'     => 'ORD-' . $prefix . '-002',
            'customer_name'    => 'Jane Smith',
            'customer_email'   => 'jane@example.com',
            'subtotal'         => 200.00,
            'discount_amount'  => 20.00,
            'tax_amount'       => 10.00,
            'shipping_amount'  => 20.00,
            'grand_total'      => 210.00,
            'status'           => 'processing',
            'payment_status'   => 'paid',
            'created_at'       => now()->subDays(1),
        ]);

        Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'tenant_branch_id' => $this->branch->id,
            'order_number'     => 'ORD-' . $prefix . '-003',
            'customer_name'    => 'Cancelled Order',
            'customer_email'   => 'cancel@example.com',
            'subtotal'         => 50.00,
            'discount_amount'  => 0.00,
            'tax_amount'       => 2.50,
            'shipping_amount'  => 5.00,
            'grand_total'      => 57.50,
            'status'           => 'cancelled',
            'payment_status'   => 'unpaid',
            'created_at'       => now()->subDays(1),
        ]);

        $analyticsService = app(AnalyticsService::class);
        $overview = $analyticsService->getSalesOverview('7_days', $this->store->id);

        $this->assertEquals(3, $overview['total_orders']);
        $this->assertEquals(1, $overview['completed_orders']);
        $this->assertEquals(1, $overview['processing_orders']);
        $this->assertEquals(1, $overview['cancelled_orders']);

        // Cancelled orders should be excluded from financial calculations
        // Gross: 110 + 210 = 320.00
        $this->assertEquals(320.00, $overview['gross_sales']);
        // Net sales: (100 + 200) - (10 + 20) = 270.00
        $this->assertEquals(270.00, $overview['net_sales']);
        // Tax total: 5 + 10 = 15.00
        $this->assertEquals(15.00, $overview['tax_total']);
        // Shipping total: 15 + 20 = 35.00
        $this->assertEquals(35.00, $overview['shipping_total']);
        // Discount total: 10 + 20 = 30.00
        $this->assertEquals(30.00, $overview['discount_total']);
        // AOV: 320 / 3 total orders = 106.67
        $this->assertEquals(106.67, $overview['average_order_value']);
    }

    /**
     * Test sales chart time-series data filling for ApexCharts.
     */
    public function test_sales_analytics_chart_data_structure(): void
    {
        $prefix = Str::random(6);

        Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'tenant_branch_id' => $this->branch->id,
            'order_number'     => 'ORD-CHART-' . $prefix,
            'customer_name'    => 'Chart Tester',
            'customer_email'   => 'chart@test.com',
            'subtotal'         => 100.00,
            'grand_total'      => 100.00,
            'status'           => 'completed',
            'payment_status'   => 'paid',
            'created_at'       => Carbon::today()->startOfDay(),
        ]);

        $analyticsService = app(AnalyticsService::class);
        $chartData = $analyticsService->getSalesChartData('7_days', $this->store->id);

        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('revenue', $chartData);
        $this->assertArrayHasKey('orders', $chartData);
        $this->assertCount(7, $chartData['labels']);
        $this->assertCount(7, $chartData['revenue']);
        $this->assertCount(7, $chartData['orders']);

        // Today's value should have at least 1 order and 100.00 revenue
        $todayRevenue = end($chartData['revenue']);
        $todayOrders = end($chartData['orders']);

        $this->assertEquals(100.00, $todayRevenue);
        $this->assertEquals(1, $todayOrders);
    }

    /**
     * Test top selling products aggregation.
     */
    public function test_top_selling_products_aggregation(): void
    {
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Top Products Cat',
            'slug'      => 'top-cat-' . Str::random(5),
            'is_active' => true,
        ]);

        $product1 = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $category->id,
            'name'        => 'Flagship Smartphone Pro',
            'slug'        => 'phone-pro-' . Str::random(5),
            'status'      => 'published',
        ]);

        $variant1 = ProductVariant::create([
            'product_id' => $product1->id,
            'sku'        => 'PHONE-PRO-' . Str::random(4),
            'price'      => 200.00,
        ]);

        $product2 = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $category->id,
            'name'        => 'Wireless Earbuds',
            'slug'        => 'earbuds-' . Str::random(5),
            'status'      => 'published',
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product2->id,
            'sku'        => 'AUDIO-BUDS-' . Str::random(4),
            'price'      => 100.00,
        ]);

        $order = Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'tenant_branch_id' => $this->branch->id,
            'order_number'     => 'ORD-TOP-' . Str::random(6),
            'customer_name'    => 'Top Buyer',
            'customer_email'   => 'buyer@test.com',
            'subtotal'         => 500.00,
            'grand_total'      => 500.00,
            'status'           => 'completed',
            'payment_status'   => 'paid',
            'created_at'       => now()->subDays(1),
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $product1->id,
            'product_variant_id' => $variant1->id,
            'product_name'       => 'Flagship Smartphone Pro',
            'variant_sku'        => $variant1->sku,
            'unit_price'         => 200.00,
            'quantity'           => 2,
            'line_total'         => 400.00,
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $product2->id,
            'product_variant_id' => $variant2->id,
            'product_name'       => 'Wireless Earbuds',
            'variant_sku'        => $variant2->sku,
            'unit_price'         => 100.00,
            'quantity'           => 1,
            'line_total'         => 100.00,
        ]);

        $analyticsService = app(AnalyticsService::class);
        $top = $analyticsService->getTopSellingProducts(5, '7_days', $this->store->id);

        $this->assertCount(2, $top);
        $this->assertEquals('Flagship Smartphone Pro', $top[0]['product_name']);
        $this->assertEquals(2, $top[0]['total_qty']);
        $this->assertEquals(400.00, $top[0]['total_revenue']);

        $this->assertEquals('Wireless Earbuds', $top[1]['product_name']);
        $this->assertEquals(1, $top[1]['total_qty']);
    }

    /**
     * Test admin vendor performance report and leaderboard calculation.
     */
    public function test_admin_vendor_performance_report_and_leaderboard(): void
    {
        $vendorUser1 = User::create([
            'name'     => 'Acme Owner',
            'email'    => 'acme.owner.' . Str::random(5) . '@sneat.test',
            'password' => bcrypt('password'),
        ]);

        $vendorUser2 = User::create([
            'name'     => 'Beta Owner',
            'email'    => 'beta.owner.' . Str::random(5) . '@sneat.test',
            'password' => bcrypt('password'),
        ]);

        $vendor1 = Vendor::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $vendorUser1->id,
            'name'            => 'Acme Electronics',
            'slug'            => 'acme-elec-' . Str::random(5),
            'email'           => 'acme@test.com',
            'commission_rate' => 15.00,
            'status'          => 'active',
            'balance'         => 850.00,
        ]);

        $vendor2 = Vendor::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $vendorUser2->id,
            'name'            => 'Beta Tech Store',
            'slug'            => 'beta-tech-' . Str::random(5),
            'email'           => 'beta@test.com',
            'commission_rate' => 10.00,
            'status'          => 'active',
            'balance'         => 450.00,
        ]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Vendor Cat',
            'slug'      => 'vendor-cat-' . Str::random(5),
            'is_active' => true,
        ]);

        $product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $category->id,
            'name'        => 'Acme Laptop',
            'slug'        => 'acme-laptop-' . Str::random(5),
            'status'      => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'ACME-LAPTOP-' . Str::random(4),
            'price'      => 1000.00,
        ]);

        $order = Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'tenant_branch_id' => $this->branch->id,
            'order_number'     => 'ORD-VEND-' . Str::random(6),
            'customer_name'    => 'Vendor Shopper',
            'customer_email'   => 'shopper@test.com',
            'subtotal'         => 1000.00,
            'grand_total'      => 1000.00,
            'status'           => 'completed',
            'payment_status'   => 'paid',
            'created_at'       => now()->subDays(2),
        ]);

        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $product->id,
            'product_variant_id' => $variant->id,
            'product_name'       => 'Acme Laptop',
            'variant_sku'        => $variant->sku,
            'unit_price'         => 1000.00,
            'quantity'           => 1,
            'line_total'         => 1000.00,
        ]);

        // Create vendor earnings
        VendorEarning::create([
            'tenant_id'         => $this->tenant->id,
            'vendor_id'         => $vendor1->id,
            'order_id'          => $order->id,
            'order_item_id'     => $orderItem->id,
            'gross_amount'      => 1000.00,
            'commission_rate'   => 15.00,
            'commission_amount' => 150.00,
            'net_amount'        => 850.00,
            'status'            => 'settled',
            'created_at'        => now()->subDays(2),
        ]);

        VendorPayout::create([
            'tenant_id'      => $this->tenant->id,
            'vendor_id'      => $vendor1->id,
            'amount'         => 300.00,
            'status'         => 'completed',
            'payment_method' => 'bank_transfer',
            'created_at'     => now()->subDays(1),
        ]);

        VendorPayout::create([
            'tenant_id'      => $this->tenant->id,
            'vendor_id'      => $vendor2->id,
            'amount'         => 200.00,
            'status'         => 'requested',
            'payment_method' => 'paypal',
            'created_at'     => now()->subDays(1),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.reports.vendors', ['period' => '30_days']));

        $response->assertStatus(200);
        $response->assertSee('Vendor Performance');
        $response->assertSee('Acme Electronics');
        $response->assertSee('Marketplace GMV');
        $response->assertSee('Platform Commission');

        $vendorService = app(VendorAnalyticsService::class);
        $overview = $vendorService->getMarketplaceOverview('30_days');

        $this->assertEquals(1000.00, $overview['marketplace_gmv']);
        $this->assertEquals(150.00, $overview['platform_commissions']);
        $this->assertEquals(850.00, $overview['vendor_net_earnings']);
        $this->assertEquals(300.00, $overview['payouts_completed']);
        $this->assertEquals(200.00, $overview['payouts_pending']);

        $leaderboard = $vendorService->getVendorLeaderboard(5, '30_days');
        $this->assertNotEmpty($leaderboard);
        $this->assertEquals('Acme Electronics', $leaderboard[0]['vendor_name']);
        $this->assertEquals(1000.00, $leaderboard[0]['total_gross']);
        $this->assertEquals(150.00, $leaderboard[0]['total_commission']);
    }

    /**
     * Test admin inventory health report and low stock queries.
     */
    public function test_admin_inventory_health_report_and_low_stock_queries(): void
    {
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Test Cat',
            'slug'      => 'test-cat-' . Str::random(5),
            'is_active' => true,
        ]);

        $product = Product::create([
            'tenant_id'   => $this->tenant->id,
            'category_id' => $category->id,
            'name'        => 'Inventory Monitor Widget',
            'slug'        => 'inventory-widget-' . Str::random(5),
            'status'      => 'published',
        ]);

        $var1 = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'WIDGET-NORMAL-' . Str::random(4),
            'price'      => 50.00,
            'cost_price' => 30.00,
        ]);

        $var2 = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'WIDGET-LOW-' . Str::random(4),
            'price'      => 80.00,
            'cost_price' => 40.00,
        ]);

        $var3 = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => 'WIDGET-OUT-' . Str::random(4),
            'price'      => 100.00,
            'cost_price' => 60.00,
        ]);

        // Stock 1: Healthy (50 on hand, 5 reserved -> 45 available > reorder 10)
        InventoryStock::create([
            'tenant_id'          => $this->tenant->id,
            'tenant_branch_id'   => $this->branch->id,
            'product_id'         => $product->id,
            'product_variant_id' => $var1->id,
            'quantity_on_hand'   => 50,
            'quantity_reserved'  => 5,
            'reorder_level'      => 10,
        ]);

        // Stock 2: Low Stock (8 on hand, 2 reserved -> 6 available <= reorder 10)
        InventoryStock::create([
            'tenant_id'          => $this->tenant->id,
            'tenant_branch_id'   => $this->branch->id,
            'product_id'         => $product->id,
            'product_variant_id' => $var2->id,
            'quantity_on_hand'   => 8,
            'quantity_reserved'  => 2,
            'reorder_level'      => 10,
        ]);

        // Stock 3: Out of Stock (2 on hand, 2 reserved -> 0 available)
        InventoryStock::create([
            'tenant_id'          => $this->tenant->id,
            'tenant_branch_id'   => $this->branch->id,
            'product_id'         => $product->id,
            'product_variant_id' => $var3->id,
            'quantity_on_hand'   => 2,
            'quantity_reserved'  => 2,
            'reorder_level'      => 5,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.reports.inventory', ['branch_id' => $this->branch->id]));

        $response->assertStatus(200);
        $response->assertSee('Inventory Health');
        $response->assertSee($var2->sku);
        $response->assertSee($var3->sku);

        $inventoryService = app(InventoryReportService::class);
        $overview = $inventoryService->getInventoryOverview($this->branch->id);

        $this->assertEquals(60, $overview['total_on_hand']); // 50 + 8 + 2
        $this->assertEquals(9, $overview['total_reserved']); // 5 + 2 + 2
        $this->assertEquals(51, $overview['total_available']); // 60 - 9
        $this->assertEquals(1, $overview['out_of_stock_count']);
        $this->assertEquals(1, $overview['low_stock_count']);

        // Valuation: (50 * 30) + (8 * 40) + (2 * 60) = 1500 + 320 + 120 = 1940.00
        $this->assertEquals(1940.00, $overview['estimated_valuation']);

        $lowStockList = $inventoryService->getLowStockItems(10, $this->branch->id);
        $this->assertCount(2, $lowStockList);
        $skus = array_column($lowStockList, 'sku');
        $this->assertContains($var3->sku, $skus);
        $this->assertContains($var2->sku, $skus);
        $this->assertNotContains($var1->sku, $skus);
    }

    /**
     * Test sales CSV export streams a valid CSV document with required columns.
     */
    public function test_sales_csv_export_streams_valid_content(): void
    {
        $uniqueNum = 'ORD-CSV-' . Str::random(6);

        Order::create([
            'tenant_id'        => $this->tenant->id,
            'store_id'         => $this->store->id,
            'tenant_branch_id' => $this->branch->id,
            'order_number'     => $uniqueNum,
            'customer_name'    => 'CSV Consumer',
            'customer_email'   => 'csv@example.com',
            'subtotal'         => 150.00,
            'discount_amount'  => 15.00,
            'tax_amount'       => 7.50,
            'shipping_amount'  => 10.00,
            'grand_total'      => 152.50,
            'status'           => 'completed',
            'payment_status'   => 'paid',
            'payment_method'   => 'stripe',
            'created_at'       => now()->subDay(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.reports.export.sales', ['period' => '7_days']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment; filename="sales_report_', $response->headers->get('Content-Disposition'));

        // Capture streamed content
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Order Number', $content);
        $this->assertStringContainsString('Customer Name', $content);
        $this->assertStringContainsString($uniqueNum, $content);
        $this->assertStringContainsString('CSV Consumer', $content);
        $this->assertStringContainsString('152.50', $content);
    }
}
