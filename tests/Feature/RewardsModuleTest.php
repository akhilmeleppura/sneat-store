<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\EcommerceStoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Order\Models\Order;
use Modules\Rewards\Models\CustomerReward;
use Modules\Rewards\Models\Reward;
use Modules\Rewards\Services\LoyaltyService;
use Tests\TestCase;

class RewardsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EcommerceStoreSeeder::class);
    }

    public function test_reward_campaign_creation_and_calculation()
    {
        $campaign = Reward::create([
            'name' => 'Holiday Loyalty Boost',
            'code' => 'HOLIDAY-2026',
            'tier' => 'gold',
            'earn_rate' => 1.50,
            'redeem_rate' => 0.0100,
            'min_points_to_redeem' => 100,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('rewards', [
            'code' => 'HOLIDAY-2026',
            'tier' => 'gold',
        ]);

        $pointsEarned = $campaign->calculatePointsEarned(200.00);
        $this->assertEquals(300, $pointsEarned);

        $discount = $campaign->calculateDiscount(500);
        $this->assertEquals(5.00, $discount);
    }

    public function test_customer_reward_earning_and_redemption_via_service()
    {
        $user = User::first() ?: User::factory()->create();
        $service = app(LoyaltyService::class);

        $customerReward = $service->getOrCreateCustomerReward($user->email, $user->id);
        $this->assertEquals(0, $customerReward->current_points);

        // Create an order
        $order = Order::create([
            'order_number'    => 'TEST-REW-' . time(),
            'store_id'        => 1,
            'user_id'         => $user->id,
            'customer_name'   => $user->name,
            'customer_email'  => $user->email,
            'subtotal'        => 150.00,
            'grand_total'     => 150.00,
            'status'          => 'completed',
            'payment_status'  => 'paid',
            'payment_method'  => 'cod',
        ]);

        // Award points
        $tx = $service->awardPointsForOrder($order);
        $this->assertNotNull($tx);
        $customerReward->refresh();
        $this->assertEquals(150, $customerReward->current_points);
        $this->assertEquals(150, $customerReward->lifetime_points);

        // Redeem points
        $redeemTx = $service->redeemPoints($customerReward, 50, $order);
        $this->assertNotNull($redeemTx);
        $customerReward->refresh();
        $this->assertEquals(100, $customerReward->current_points);
        $this->assertEquals('redeemed', $redeemTx->type);
    }

    public function test_customer_rewards_balance_api()
    {
        $customer = CustomerReward::create([
            'customer_email' => 'api.loyalty@sneat.test',
            'current_points' => 250,
            'lifetime_points' => 500,
            'tier' => 'Silver',
        ]);

        $response = $this->getJson('/store/rewards/balance?email=api.loyalty@sneat.test');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'current_points' => 250,
                'tier' => 'Silver',
            ]);
    }

    public function test_rewards_admin_index()
    {
        $user = User::first() ?: User::factory()->create();
        $response = $this->actingAs($user)->get(route('admin.rewards.index'));

        $response->assertStatus(200);
        $response->assertSee('Rewards & Loyalty');
    }
}
