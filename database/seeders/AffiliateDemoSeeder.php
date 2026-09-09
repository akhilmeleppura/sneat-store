<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Context\Models\Tenant;
use Modules\Order\Models\Affiliate;
use Modules\Order\Models\AffiliateReferral;
use Modules\Order\Models\Order;

class AffiliateDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        if (!$tenant) {
            return;
        }
        $tenantId = $tenant->id;

        $user1 = User::firstOrCreate(
            ['email' => 'alex.influencer@gmail.com'],
            ['name' => 'Alex Rivera', 'password' => bcrypt('password')]
        );

        $affiliate1 = Affiliate::withoutTenancy()->firstOrCreate(
            ['user_id' => $user1->id],
            [
                'tenant_id'        => $tenantId,
                'affiliate_code'   => 'ALEXVIP',
                'commission_rate'  => 12.00,
                'total_earnings'   => 450.00,
                'pending_earnings' => 150.00,
                'paid_earnings'    => 300.00,
                'status'           => 'active',
                'payout_method'    => 'paypal',
                'payout_account'   => 'alex.rivera.pay@gmail.com',
            ]
        );

        $user2 = User::firstOrCreate(
            ['email' => 'techreviews@youtube.com'],
            ['name' => 'TechPulse Reviews', 'password' => bcrypt('password')]
        );

        $affiliate2 = Affiliate::withoutTenancy()->firstOrCreate(
            ['user_id' => $user2->id],
            [
                'tenant_id'        => $tenantId,
                'affiliate_code'   => 'TECH10',
                'commission_rate'  => 10.00,
                'total_earnings'   => 820.00,
                'pending_earnings' => 320.00,
                'paid_earnings'    => 500.00,
                'status'           => 'active',
                'payout_method'    => 'bank_transfer',
                'payout_account'   => 'IBAN: US93TECH849201948201',
            ]
        );
    }
}
