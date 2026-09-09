<?php

namespace Modules\Rewards\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Context\Services\TenantContext;
use Modules\Order\Models\Order;
use Modules\Rewards\Models\CustomerReward;
use Modules\Rewards\Models\LoyaltyTransaction;
use Modules\Rewards\Models\Reward;

class LoyaltyService
{
    /**
     * Get the active reward campaign for tenant.
     */
    public function getActiveCampaign(): ?Reward
    {
        return Reward::active()->first();
    }

    /**
     * Get or create customer reward profile by email or user.
     */
    public function getOrCreateCustomerReward(string $email, ?int $userId = null): CustomerReward
    {
        $tenantId = app()->has(TenantContext::class) ? app(TenantContext::class)->getTenantId() : null;

        $record = CustomerReward::where('customer_email', $email)->first();

        if (! $record) {
            $record = CustomerReward::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'customer_email' => $email,
                'current_points' => 0,
                'lifetime_points' => 0,
                'tier' => 'Bronze',
            ]);
        } elseif ($userId && ! $record->user_id) {
            $record->user_id = $userId;
            $record->save();
        }

        return $record;
    }

    /**
     * Credit loyalty points for an order.
     */
    public function awardPointsForOrder(Order $order): ?LoyaltyTransaction
    {
        $campaign = $this->getActiveCampaign();
        $rate = $campaign ? (float) $campaign->earn_rate : 1.00;
        $pointsEarned = (int) floor((float) $order->grand_total * $rate);

        if ($pointsEarned <= 0) {
            return null;
        }

        return DB::transaction(function () use ($order, $pointsEarned) {
            $customerReward = $this->getOrCreateCustomerReward($order->customer_email, $order->user_id);

            $newBalance = $customerReward->current_points + $pointsEarned;
            $customerReward->current_points = $newBalance;
            $customerReward->lifetime_points += $pointsEarned;
            $customerReward->updateTier();
            $customerReward->save();

            return LoyaltyTransaction::create([
                'tenant_id' => $order->tenant_id,
                'customer_reward_id' => $customerReward->id,
                'order_id' => $order->id,
                'type' => 'earned',
                'points' => $pointsEarned,
                'balance_after' => $newBalance,
                'description' => "Earned {$pointsEarned} points on Order #{$order->order_number}",
                'metadata' => [
                    'order_number' => $order->order_number,
                    'grand_total' => $order->grand_total,
                ],
            ]);
        });
    }

    /**
     * Redeem points from customer balance for an order or discount.
     */
    public function redeemPoints(CustomerReward $customerReward, int $points, ?Order $order = null): LoyaltyTransaction
    {
        if ($points <= 0) {
            throw new Exception('Redemption points must be greater than zero.');
        }

        if ($customerReward->current_points < $points) {
            throw new Exception('Insufficient loyalty points balance.');
        }

        $campaign = $this->getActiveCampaign();
        if ($campaign && $campaign->min_points_to_redeem && $points < $campaign->min_points_to_redeem) {
            throw new Exception("Minimum {$campaign->min_points_to_redeem} points required to redeem.");
        }

        return DB::transaction(function () use ($customerReward, $points, $order, $campaign) {
            $newBalance = $customerReward->current_points - $points;
            $customerReward->current_points = $newBalance;
            $customerReward->save();

            $redeemRate = $campaign ? (float) $campaign->redeem_rate : 0.01;
            $discountVal = round($points * $redeemRate, 2);

            return LoyaltyTransaction::create([
                'tenant_id' => $customerReward->tenant_id,
                'customer_reward_id' => $customerReward->id,
                'order_id' => $order?->id,
                'type' => 'redeemed',
                'points' => -$points,
                'balance_after' => $newBalance,
                'description' => "Redeemed {$points} points (\${$discountVal} discount)" . ($order ? " on Order #{$order->order_number}" : ''),
                'metadata' => [
                    'discount_value' => $discountVal,
                    'order_id' => $order?->id,
                ],
            ]);
        });
    }
}
