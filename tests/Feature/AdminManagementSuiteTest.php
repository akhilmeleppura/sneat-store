<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\BackInStockSubscription;
use Modules\Catalog\Models\Product;
use Modules\General\Models\NewsletterSubscriber;
use Modules\Order\Models\GiftCard;
use Modules\Order\Models\LoyaltyPoint;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderRmaRequest;
use Modules\Order\Models\RfqQuote;
use Modules\Order\Models\TaxRate;
use Tests\TestCase;

class AdminManagementSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);

        $this->adminUser = User::first() ?: User::factory()->create();
        $this->adminUser->is_supreme_admin = 1;
        $this->adminUser->save();
    }

    public function test_admin_rma_page_and_status_update()
    {
        $rma = OrderRmaRequest::create([
            'tenant_id'       => 1,
            'order_id'        => 1,
            'user_id'         => $this->adminUser->id,
            'rma_number'      => 'RMA-TEST-1001',
            'reason'          => 'Product malfunctioning',
            'condition'       => 'opened',
            'resolution_type' => 'refund',
            'status'          => 'pending',
        ]);

        // GET Admin RMA
        $response = $this->actingAs($this->adminUser)->get('/admin/rma');
        $response->assertStatus(200)
            ->assertSee('Return Authorizations (RMA)')
            ->assertSee('RMA-TEST-1001');

        // POST Update Status
        $updateResponse = $this->actingAs($this->adminUser)->post("/admin/rma/{$rma->id}/status", [
            'status'                 => 'approved',
            'admin_notes'            => 'Approved for full refund',
            'return_tracking_number' => 'TRACK-RMA-8899',
        ]);

        $updateResponse->assertRedirect();
        $this->assertEquals('approved', $rma->fresh()->status);
        $this->assertEquals('TRACK-RMA-8899', $rma->fresh()->return_tracking_number);

        // Verify customer received notification
        $latestNotification = $this->adminUser->fresh()->notifications()->first();
        $this->assertNotNull($latestNotification);
        $this->assertEquals('rma_status_update', $latestNotification->data['type']);
        $this->assertEquals('approved', $latestNotification->data['status']);
    }

    public function test_customer_rma_portal()
    {
        $order = Order::create([
            'order_number'    => 'ORD-DELIVERED-01',
            'store_id'        => 1,
            'user_id'         => $this->adminUser->id,
            'customer_name'   => $this->adminUser->name,
            'customer_email'  => $this->adminUser->email,
            'subtotal'        => 99.00,
            'grand_total'     => 99.00,
            'status'          => 'delivered',
            'payment_status'  => 'paid',
            'payment_method'  => 'stripe',
            'shipping_method' => 'Standard',
            'shipping_amount' => 0.00,
            'tax_amount'      => 0.00,
        ]);

        // GET Customer RMA page
        $res = $this->actingAs($this->adminUser)->get('/account/rma');
        $res->assertStatus(200)
            ->assertSee('Return Requests & RMA', false);

        // POST Customer submits RMA
        $storeRes = $this->actingAs($this->adminUser)->post('/account/rma', [
            'order_id'        => $order->id,
            'reason'          => 'Received wrong item',
            'condition'       => 'unopened',
            'resolution_type' => 'refund',
        ]);

        $storeRes->assertRedirect(route('account.rma.index'));
        $this->assertDatabaseHas('order_rma_requests', [
            'order_id' => $order->id,
            'user_id'  => $this->adminUser->id,
            'reason'   => 'Received wrong item',
        ]);
    }

    public function test_admin_rfq_management_and_proposal()
    {
        $quote = RfqQuote::create([
            'quote_number'  => 'RFQ-CORP-9001',
            'company_name'  => 'Nexus Global Tech',
            'contact_name'  => 'Elena Rostova',
            'contact_email' => 'elena@nexus.example',
            'contact_phone' => '+1555444333',
            'tax_id'        => 'VAT-GB-123456789',
            'items_payload' => [
                ['name' => 'Pro Enterprise Router', 'sku' => 'NET-ROUTER-01', 'quantity' => 20, 'target_price' => 120.00],
            ],
            'status'        => 'pending',
            'notes'         => 'Require urgent bulk delivery.',
        ]);

        // GET Admin RFQ Index
        $indexRes = $this->actingAs($this->adminUser)->get('/admin/rfq');
        $indexRes->assertStatus(200)
            ->assertSee('Nexus Global Tech')
            ->assertSee('RFQ-CORP-9001');

        // GET Admin RFQ Show
        $showRes = $this->actingAs($this->adminUser)->get("/admin/rfq/{$quote->id}");
        $showRes->assertStatus(200)
            ->assertSee('Nexus Global Tech')
            ->assertSee('Pro Enterprise Router');

        // POST Proposal
        $propRes = $this->actingAs($this->adminUser)->post("/admin/rfq/{$quote->id}/proposal", [
            'quoted_total' => 2200.00,
            'valid_days'   => 21,
            'notes'        => 'Includes expedited air freight',
        ]);

        $propRes->assertRedirect(route('admin.rfq.show', $quote->id));
        $quote->refresh();
        $this->assertEquals(2200.00, (float) $quote->quoted_total);
        $this->assertEquals('quoted', $quote->status);

        // POST Status change
        $statRes = $this->actingAs($this->adminUser)->post("/admin/rfq/{$quote->id}/status", [
            'status' => 'accepted',
        ]);
        $statRes->assertRedirect();
        $this->assertEquals('accepted', $quote->fresh()->status);
    }

    public function test_admin_gift_cards_and_storefront_checker()
    {
        // 1. GET Admin Gift Cards Index
        $getRes = $this->actingAs($this->adminUser)->get('/admin/gift-cards');
        $getRes->assertStatus(200)
            ->assertSee('Gift Cards');

        // 2. POST Issue Gift Card
        $postRes = $this->actingAs($this->adminUser)->post('/admin/gift-cards', [
            'amount'          => 150.00,
            'currency'        => 'USD',
            'recipient_email' => 'gift.recipient@example.com',
            'valid_days'      => 365,
        ]);

        $postRes->assertRedirect();
        $card = GiftCard::where('recipient_email', 'gift.recipient@example.com')->first();
        $this->assertNotNull($card);
        $this->assertEquals(150.00, (float) $card->initial_balance);
        $this->assertEquals(150.00, (float) $card->current_balance);

        // 3. POST Toggle Gift Card
        $toggleRes = $this->actingAs($this->adminUser)->post("/admin/gift-cards/{$card->id}/toggle");
        $toggleRes->assertRedirect();
        $this->assertFalse($card->fresh()->is_active);

        GiftCard::withoutTenancy()->where('id', $card->id)->update(['is_active' => true]);
        $card->refresh();

        // 4. GET Public Gift Card Balance page
        $checkerPageRes = $this->get('/gift-cards');
        $checkerPageRes->assertStatus(200)
            ->assertSee('Check Your Gift Card Balance');

        // 5. POST Check Balance AJAX
        $checkRes = $this->postJson('/store/gift-cards/check', [
            'code' => $card->code,
        ]);
        $checkRes->assertStatus(200)
            ->assertJson([
                'status'          => 'success',
                'code'            => $card->code,
                'current_balance' => 150.00,
            ]);
    }

    public function test_back_in_stock_subscriptions_dashboard_and_notify()
    {
        $product = Product::first();

        $sub = BackInStockSubscription::create([
            'tenant_id'   => 1,
            'product_id'  => $product->id,
            'email'       => 'shopper.waiting@example.com',
            'phone'       => '+1555111222',
            'is_notified' => false,
        ]);

        // GET Admin Back in Stock
        $res = $this->actingAs($this->adminUser)->get('/admin/back-in-stock');
        $res->assertStatus(200)
            ->assertSee('Back-in-Stock Notifications')
            ->assertSee('shopper.waiting@example.com');

        // POST Notify subscribers
        $notifyRes = $this->actingAs($this->adminUser)->post("/admin/back-in-stock/{$product->id}/notify");
        $notifyRes->assertRedirect();

        $this->assertTrue($sub->fresh()->is_notified);
        $this->assertNotNull($sub->fresh()->notified_at);
    }

    public function test_newsletter_subscribers_admin_management()
    {
        $sub = NewsletterSubscriber::create([
            'tenant_id'   => 1,
            'email'       => 'newsletter.fan@example.com',
            'status'      => 'subscribed',
            'token'       => 'test-newsletter-token-abc',
            'verified_at' => now(),
            'ip_address'  => '127.0.0.1',
        ]);

        // GET Admin Newsletter
        $res = $this->actingAs($this->adminUser)->get('/admin/newsletter');
        $res->assertStatus(200)
            ->assertSee('Newsletter Subscribers')
            ->assertSee('newsletter.fan@example.com');

        // GET CSV Export
        $exportRes = $this->actingAs($this->adminUser)->get('/admin/newsletter/export');
        $exportRes->assertStatus(200);
        $this->assertStringContainsString('text/csv', $exportRes->headers->get('Content-Type') ?: '');

        // POST Toggle Status
        $toggleRes = $this->actingAs($this->adminUser)->post("/admin/newsletter/{$sub->id}/toggle");
        $toggleRes->assertRedirect();
        $this->assertEquals('unsubscribed', $sub->fresh()->status);

        // DELETE Subscriber
        $delRes = $this->actingAs($this->adminUser)->delete("/admin/newsletter/{$sub->id}");
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $sub->id]);
    }

    public function test_admin_tax_rates_management()
    {
        // 1. GET Tax Rates
        $res = $this->actingAs($this->adminUser)->get('/admin/taxes');
        $res->assertStatus(200)
            ->assertSee('Regional Taxes & VAT', false);

        // 2. POST Create Tax Rate
        $createRes = $this->actingAs($this->adminUser)->post('/admin/taxes', [
            'country_code'    => 'US',
            'state_code'      => 'TX',
            'tax_name'        => 'Texas State Sales Tax',
            'rate_percentage' => 6.25,
            'is_compound'     => 0,
            'is_b2b_exempt'   => 1,
            'is_active'       => 1,
        ]);

        $createRes->assertRedirect();
        $tax = TaxRate::withoutTenancy()->where('state_code', 'TX')->first();
        $this->assertNotNull($tax);
        $this->assertEquals(6.25, (float) $tax->rate_percentage);

        // 3. PUT Update Tax Rate
        $updateRes = $this->actingAs($this->adminUser)->put("/admin/taxes/{$tax->id}", [
            'country_code'    => 'US',
            'state_code'      => 'TX',
            'tax_name'        => 'Texas State Sales Tax (Updated)',
            'rate_percentage' => 8.25,
            'is_compound'     => 1,
            'is_b2b_exempt'   => 1,
            'is_active'       => 1,
        ]);
        $updateRes->assertRedirect();
        $this->assertEquals(8.25, (float) $tax->fresh()->rate_percentage);
        $this->assertTrue($tax->fresh()->is_compound);

        // 4. POST Toggle
        $toggleRes = $this->actingAs($this->adminUser)->post("/admin/taxes/{$tax->id}/toggle");
        $toggleRes->assertRedirect();
        $this->assertFalse($tax->fresh()->is_active);

        // 5. DELETE
        $delRes = $this->actingAs($this->adminUser)->delete("/admin/taxes/{$tax->id}");
        $delRes->assertRedirect();
        $this->assertDatabaseMissing('tax_rates', ['id' => $tax->id]);
    }

    public function test_customer_loyalty_rewards_page()
    {
        LoyaltyPoint::create([
            'tenant_id'     => 1,
            'user_id'       => $this->adminUser->id,
            'points_change' => 250,
            'balance_after' => 250,
            'type'          => 'purchase',
            'description'   => 'Welcome bonus loyalty reward points',
        ]);

        $res = $this->actingAs($this->adminUser)->get('/account/loyalty');
        $res->assertStatus(200)
            ->assertSee('Loyalty Reward Points')
            ->assertSee('Welcome bonus loyalty reward points')
            ->assertSee('250');
    }
}
