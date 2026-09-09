<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Order\Models\Order;
use Modules\Order\Models\RfqQuote;
use Modules\Order\Services\RfqService;
use Modules\Order\Services\RmaService;
use Tests\TestCase;

class RmaAndWholesaleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_customer_and_admin_rma_workflow()
    {
        $user = User::first() ?: User::factory()->create();

        $order = Order::create([
            'order_number'    => 'TEST-ORD-RMA-001',
            'store_id'        => 1,
            'user_id'         => $user->id,
            'customer_name'   => $user->name,
            'customer_email'  => $user->email,
            'subtotal'        => 150.00,
            'grand_total'     => 150.00,
            'status'          => 'delivered',
            'payment_status'  => 'paid',
            'payment_method'  => 'stripe',
            'shipping_method' => 'Standard',
            'shipping_amount' => 0.00,
            'tax_amount'      => 0.00,
        ]);

        // 1. Customer initiates RMA
        $response = $this->actingAs($user)->postJson('/account/rma/request', [
            'order_id'        => $order->id,
            'reason'          => 'Defective audio channel on left ear cup',
            'condition'       => 'opened',
            'resolution_type' => 'exchange',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure(['status', 'rma_number', 'message']);

        $rmaNumber = $response->json('rma_number');

        // 2. Admin views RMA requests
        $adminResponse = $this->actingAs($user)->getJson('/admin/rma');
        $adminResponse->assertStatus(200)
            ->assertJsonStructure(['status', 'data']);

        // 3. Admin updates RMA status to approved with tracking
        $rmaRecord = \Modules\Order\Models\OrderRmaRequest::where('rma_number', $rmaNumber)->first();
        $statusResponse = $this->actingAs($user)->postJson("/admin/rma/{$rmaRecord->id}/status", [
            'status'                 => 'approved',
            'admin_notes'            => 'Approved for return. Return label dispatched.',
            'return_tracking_number' => 'RET-FDX-998877',
        ]);

        $statusResponse->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertEquals('approved', $rmaRecord->fresh()->status);
        $this->assertEquals('RET-FDX-998877', $rmaRecord->fresh()->return_tracking_number);
    }

    public function test_b2b_rfq_workflow()
    {
        $response = $this->postJson('/b2b/rfq', [
            'company_name'  => 'Apex Enterprise Solutions LLC',
            'contact_name'  => 'Marcus Vance',
            'contact_email' => 'marcus.vance@apex.corp',
            'contact_phone' => '+1 (555) 777-8899',
            'tax_id'        => 'US-EIN-99223344',
            'items'         => [
                ['sku' => 'LAPTOP-PRO-01', 'qty' => 50],
                ['sku' => 'MONITOR-4K-02', 'qty' => 50],
            ],
            'notes'         => 'Need delivery to central distribution by Q4.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ])
            ->assertJsonStructure(['status', 'quote_number', 'message']);

        $quoteNumber = $response->json('quote_number');

        // Merchant quotes pricing
        $quote = RfqQuote::where('quote_number', $quoteNumber)->first();
        $rfqService = app(RfqService::class);
        $quoted = $rfqService->quotePrice($quote->id, 45000.00);

        $this->assertEquals('quoted', $quoted->status);
        $this->assertEquals(45000.00, (float) $quoted->quoted_total);

        // Buyer accepts
        $accepted = $rfqService->acceptQuote($quote->id);
        $this->assertEquals('accepted', $accepted->status);
    }

    public function test_barcode_label_and_csv_export()
    {
        $user = User::first() ?: User::factory()->create();
        $product = Product::first();

        // 1. Barcode print sheet
        $barcodeRes = $this->actingAs($user)->get("/admin/inventory/barcodes/{$product->id}");
        $barcodeRes->assertStatus(200);
        $barcodeRes->assertSee($product->name);

        // 2. CSV Product Export
        $exportRes = $this->actingAs($user)->get('/catalog/export-csv');
        $exportRes->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportRes->headers->get('Content-Type') ?: '');
    }
}
