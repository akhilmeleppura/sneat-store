<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Accounting\App\Models\ChartOfAccount;
use Modules\Accounting\App\Models\JournalEntries;
use Modules\Accounting\App\Models\JournalIndex;
use Modules\Billing\App\Models\BillingInvoice;
use Modules\Billing\App\Models\BillingInvoiceItem;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Models\InventoryStock;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Payment\Services\FinancialSettlementService;
use Modules\Payment\Services\PaymentManager;
use Tests\TestCase;

class PaymentSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;
    protected ProductVariant $variant;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Payment Test Tenant',
            'slug' => 'pay-tenant-' . Str::random(5),
        ]);

        $this->store = Store::create([
            'tenant_id'  => $this->tenant->id,
            'name'       => 'Payment Test Store',
            'slug'       => 'pay-store-' . Str::random(5),
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id'  => $this->tenant->id,
            'store_id'   => $this->store->id,
            'name'       => 'Payment Central Branch',
            'slug'       => 'pay-br-' . Str::random(5),
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);

        $category = Category::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'slug' => 'pay-electronics'],
            ['name' => 'Electronics', 'is_active' => true]
        );

        $brand = Brand::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'slug' => 'pay-sony'],
            ['name' => 'Sony', 'is_active' => true]
        );

        $product = Product::firstOrCreate(
            ['tenant_id' => $this->tenant->id, 'sku' => 'PROD-PAY-01'],
            [
                'category_id' => $category->id,
                'brand_id'    => $brand->id,
                'name'        => 'Sony WH-1000XM5',
                'slug'        => 'sony-wh-1000xm5-pay',
                'is_active'   => true,
            ]
        );

        $this->variant = ProductVariant::firstOrCreate(
            ['product_id' => $product->id, 'sku' => 'SKU-PAY-SONY-BLK'],
            [
                'name'           => 'Black Edition',
                'price'          => 399.00,
                'manage_stock'   => true,
                'stock_quantity' => 50,
            ]
        );

        // Add initial stock via InventoryService
        app(\Modules\Inventory\Services\InventoryService::class)->adjustStock(
            $this->variant->id,
            $this->branch->id,
            50,
            'initial',
            'init',
            null,
            'Test setup'
        );

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin-payment@sneat.test'],
            [
                'name'              => 'Payment Platform Admin',
                'password'          => bcrypt('password'),
                'tenant_id'         => null,
                'email_verified_at' => now(),
            ]
        );
    }

    /**
     * Helper to create a realistic test order.
     */
    protected function createTestOrder(string $paymentMethod = 'mock', string $paymentStatus = 'unpaid'): Order
    {
        $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);

        $order = Order::create([
            'tenant_id'          => $this->tenant->id,
            'store_id'           => $this->store->id,
            'tenant_branch_id'   => $this->branch->id,
            'user_id'            => $this->adminUser->id,
            'order_number'       => $orderNumber,
            'customer_name'      => 'Alice Johnson',
            'customer_email'     => 'alice.johnson@example.com',
            'customer_phone'     => '+1 555-0199',
            'shipping_address'   => [
                'street'      => '742 Evergreen Terrace',
                'city'        => 'Springfield',
                'state'       => 'OR',
                'country'     => 'USA',
                'postal_code' => '97477',
            ],
            'subtotal'           => 399.00,
            'discount_amount'    => 0.00,
            'tax_amount'         => 39.90,
            'shipping_amount'    => 15.00,
            'grand_total'        => 453.90,
            'currency'           => 'USD',
            'status'             => 'pending',
            'payment_status'     => $paymentStatus,
            'payment_method'     => $paymentMethod,
            'fulfillment_status' => 'unfulfilled',
        ]);

        OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $this->variant->product_id,
            'product_variant_id' => $this->variant->id,
            'product_name'       => 'Sony WH-1000XM5 (Black)',
            'variant_sku'        => $this->variant->sku,
            'unit_price'         => 399.00,
            'quantity'           => 1,
            'tax_amount'         => 39.90,
            'discount_amount'    => 0.00,
            'line_total'         => 399.00,
        ]);

        return $order;
    }

    /**
     * Test payment gateway charge and transaction logging.
     */
    public function test_payment_gateway_dispatch_and_mock_charge(): void
    {
        $order = $this->createTestOrder('mock', 'unpaid');

        $paymentManager = app(PaymentManager::class);
        $driver = $paymentManager->driver('mock');
        $response = $driver->charge($order);

        $this->assertTrue($response->successful);
        $this->assertNotNull($response->transactionReference);

        $transaction = PaymentTransaction::where('order_id', $order->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('mock', $transaction->gateway);
        $this->assertEquals(453.90, (float) $transaction->amount);
        $this->assertEquals('successful', $transaction->status);
    }

    /**
     * Test automated financial settlement into Billing and Accounting.
     */
    public function test_financial_settlement_creates_billing_invoice_and_journal_entries(): void
    {
        $order = $this->createTestOrder('stripe', 'unpaid');

        $settlementService = app(FinancialSettlementService::class);
        $result = $settlementService->settleOrder($order, 'stripe_txn_12345');

        $this->assertEquals('settled', $result['status']);

        // 1. Check BillingInvoice
        $invoice = BillingInvoice::find($result['invoice_id']);
        $this->assertNotNull($invoice);
        $this->assertEquals(1, $invoice->payment_status);
        $this->assertEquals(399.00, (float) $invoice->sub_total);

        // Check BillingInvoiceItems
        $items = BillingInvoiceItem::where('document_id', $invoice->id)->get();
        $this->assertCount(1, $items);
        $this->assertEquals(399.00, (float) $items->first()->selling_unit_price);

        // 2. Check Accounting General Ledger
        $journal = JournalIndex::find($result['journal_id']);
        $this->assertNotNull($journal);
        $this->assertEquals(453.90, (float) $journal->transaction_amount);

        // Check balanced debits and credits
        $entries = JournalEntries::where('journal_id', $journal->id)->get();
        $totalDebit = $entries->sum('debit_amount');
        $totalCredit = $entries->sum('credit_amount');

        $this->assertEquals(453.90, (float) $totalDebit, 'Total general ledger debits must match order grand total');
        $this->assertEquals(453.90, (float) $totalCredit, 'Total general ledger credits must equal total debits');

        // Verify Order updated
        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('fulfilled', $order->fulfillment_status);
        $this->assertNotNull($order->metadata['invoice_number']);
        $this->assertNotNull($order->metadata['journal_number']);
    }

    /**
     * Test idempotency: multiple settlements on the same order do not duplicate invoices.
     */
    public function test_settlement_idempotency(): void
    {
        $order = $this->createTestOrder('paypal', 'unpaid');

        $settlementService = app(FinancialSettlementService::class);

        // Run settlement 1
        $result1 = $settlementService->settleOrder($order, 'paypal_ord_111');
        $this->assertEquals('settled', $result1['status']);

        $order->refresh();

        // Run settlement 2
        $result2 = $settlementService->settleOrder($order, 'paypal_ord_111');
        $this->assertEquals('already_settled', $result2['status']);

        // Assert only 1 invoice exists for this order
        $invoiceCount = BillingInvoice::where('document_number', 'INV-' . str_replace('ORD-', '', $order->order_number))->count();
        $this->assertEquals(1, $invoiceCount);

        // Assert only 1 journal index exists
        $journalCount = JournalIndex::where('journal_number', 'JRN-' . str_replace('ORD-', '', $order->order_number))->count();
        $this->assertEquals(1, $journalCount);
    }

    /**
     * Test webhook endpoint processing.
     */
    public function test_webhook_endpoint_processing(): void
    {
        $order = $this->createTestOrder('mock', 'unpaid');

        $response = $this->postJson("/api/webhooks/mock", [
            'order_number'          => $order->order_number,
            'transaction_reference' => 'mock_hook_ref_999',
            'status'                => 'successful',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'received' => true,
            'order'    => $order->order_number,
        ]);

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
    }

    /**
     * Test admin order status transition to paid triggers automated settlement.
     */
    public function test_admin_order_status_update_triggers_settlement(): void
    {
        $order = $this->createTestOrder('cod', 'unpaid');

        $response = $this->actingAs($this->adminUser)
            ->withHeaders(['X-Tenant-Key' => $this->tenant->slug])
            ->post("/admin/orders/{$order->id}/status", [
                'status'             => 'processing',
                'payment_status'     => 'paid',
                'fulfillment_status' => 'fulfilled',
            ]);

        $response->assertRedirect(route('admin.orders.show', $order->id));

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertNotEmpty($order->metadata['invoice_id']);
        $this->assertNotEmpty($order->metadata['journal_index_id']);
    }

    /**
     * Test admin can view payments and issue refund.
     */
    public function test_admin_can_view_payments_and_issue_refund(): void
    {
        $order = $this->createTestOrder('mock', 'paid');

        $ref = 'mock_txn_ref_' . Str::random(10);
        $transaction = PaymentTransaction::create([
            'tenant_id'              => $this->tenant->id,
            'order_id'               => $order->id,
            'transaction_reference'  => $ref,
            'gateway'                => 'mock',
            'amount'                 => $order->grand_total,
            'currency'               => 'USD',
            'status'                 => 'successful',
            'payment_method_details' => ['brand' => 'Visa', 'last4' => '4242'],
        ]);

        // 1. Visit admin index
        $indexResponse = $this->actingAs($this->adminUser)
            ->withHeaders(['X-Tenant-Key' => $this->tenant->slug])
            ->get('/admin/payments');

        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($transaction->transaction_reference);

        // 2. Issue refund
        $refundResponse = $this->actingAs($this->adminUser)
            ->withHeaders(['X-Tenant-Key' => $this->tenant->slug])
            ->post("/admin/payments/{$transaction->id}/refund", [
                'amount' => 50.00,
                'reason' => 'Partial refund for returned accessory',
            ]);

        $refundResponse->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('refunded', $transaction->status);
        $this->assertEquals(50.00, $transaction->payload['refund_amount']);
    }
}
