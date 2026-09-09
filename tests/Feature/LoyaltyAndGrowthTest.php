<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Order\Models\Order;
use Modules\Order\Services\GiftCardService;
use Modules\Order\Services\LoyaltyService;
use Tests\TestCase;

class LoyaltyAndGrowthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_loyalty_points_earning_and_redemption()
    {
        $user = User::first() ?: User::factory()->create();
        $loyaltyService = app(LoyaltyService::class);

        // 1. Initial balance is 0
        $this->assertEquals(0, $loyaltyService->getUserBalance($user->id));

        // 2. Award points for an order
        $order = Order::create([
            'order_number'    => 'TEST-ORD-LOYALTY-001',
            'store_id'        => 1,
            'user_id'         => $user->id,
            'customer_name'   => $user->name,
            'customer_email'  => $user->email,
            'subtotal'        => 100.00,
            'grand_total'     => 100.00,
            'status'          => 'completed',
            'payment_status'  => 'paid',
            'payment_method'  => 'stripe',
            'shipping_method' => 'Standard',
            'shipping_amount' => 0.00,
            'tax_amount'      => 0.00,
        ]);

        $pointRecord = $loyaltyService->awardOrderPoints($order);
        $this->assertNotNull($pointRecord);
        $this->assertEquals(100, $pointRecord->points_change);
        $this->assertEquals(100, $loyaltyService->getUserBalance($user->id));

        // 3. Redeem points
        $redemption = $loyaltyService->redeemPoints($user->id, 40);
        $this->assertEquals('success', $redemption['status']);
        $this->assertEquals(2.00, $redemption['discount_value']); // 40 * 0.05
        $this->assertEquals(60, $loyaltyService->getUserBalance($user->id));
    }

    public function test_gift_card_issuance_and_balance_check()
    {
        $giftCardService = app(GiftCardService::class);
        $card = $giftCardService->issueGiftCard(50.00, 'USD', 'friend@example.com');

        $this->assertNotNull($card->code);
        $this->assertEquals(50.00, $card->current_balance);

        // Check balance endpoint
        $response = $this->postJson('/store/gift-cards/check', [
            'code' => $card->code,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status'          => 'success',
                'current_balance' => 50.00,
            ]);

        // Deduct balance
        $deducted = $giftCardService->deductBalance($card->code, 20.00);
        $this->assertTrue($deducted);
        $this->assertEquals(30.00, $card->fresh()->current_balance);
    }

    public function test_back_in_stock_subscription()
    {
        $product = Product::first();

        $response = $this->postJson('/store/back-in-stock/subscribe', [
            'product_id' => $product->id,
            'email'      => 'waiting.buyer@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('back_in_stock_subscriptions', [
            'product_id' => $product->id,
            'email'      => 'waiting.buyer@example.com',
            'is_notified'=> false,
        ]);
    }

    public function test_newsletter_subscription()
    {
        $response = $this->postJson('/newsletter/subscribe', [
            'email' => 'subscriber@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email'  => 'subscriber@example.com',
            'status' => 'subscribed',
        ]);
    }
}
