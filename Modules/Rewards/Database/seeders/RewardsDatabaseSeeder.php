<?php

namespace Modules\Rewards\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Context\Models\Tenant;
use Modules\Rewards\Models\CustomerReward;
use Modules\Rewards\Models\LoyaltyTransaction;
use Modules\Rewards\Models\Reward;

if (! class_exists('Modules\Rewards\Database\Seeders\RewardsDatabaseSeeder')) {
class RewardsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        $tenantId = $tenant?->id;

        // 1. Standard Loyalty Program
        $standard = Reward::firstOrCreate(
            ['code' => 'LOYALTY-STD'],
            [
                'tenant_id' => $tenantId,
                'name' => 'Standard Member Rewards',
                'description' => 'Earn 1 point for every $1 spent. Redeem 100 points for $1 off checkout.',
                'tier' => 'standard',
                'earn_rate' => 1.00,
                'redeem_rate' => 0.0100,
                'min_points_to_redeem' => 50,
                'max_points_per_order' => 1000,
                'is_active' => true,
            ]
        );

        // 2. VIP Gold Tier Campaign
        $gold = Reward::firstOrCreate(
            ['code' => 'LOYALTY-GOLD'],
            [
                'tenant_id' => $tenantId,
                'name' => 'Gold VIP Tier Multiplier',
                'description' => 'Earn 2x points on all orders for Gold VIP members.',
                'tier' => 'gold',
                'earn_rate' => 2.00,
                'redeem_rate' => 0.0150,
                'min_points_to_redeem' => 100,
                'max_points_per_order' => 2500,
                'is_active' => true,
            ]
        );

        // 3. Sample Customer Rewards
        $sampleCustomer = CustomerReward::firstOrCreate(
            ['customer_email' => 'customer@sneatstore.com'],
            [
                'tenant_id' => $tenantId,
                'user_id' => null,
                'current_points' => 350,
                'lifetime_points' => 850,
                'tier' => 'Silver',
            ]
        );

        LoyaltyTransaction::firstOrCreate(
            ['customer_reward_id' => $sampleCustomer->id, 'type' => 'bonus'],
            [
                'tenant_id' => $tenantId,
                'points' => 350,
                'balance_after' => 350,
                'description' => 'Welcome Loyalty Bonus & Initial Store Purchase Points',
            ]
        );
    }
}
}
