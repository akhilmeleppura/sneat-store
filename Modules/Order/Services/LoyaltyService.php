<?php

namespace Modules\Order\Services;

use Modules\Order\Models\LoyaltyPoint;
use Modules\Order\Models\Order;

class LoyaltyService
{
    public const POINTS_PER_DOLLAR_EARNED = 1;
    public const VALUE_PER_POINT = 0.05; // 100 points = $5.00

    /**
     * Get user's current points balance.
     */
    public function getUserBalance(int $userId): int
    {
        $last = LoyaltyPoint::withoutTenancy()->where('user_id', $userId)
            ->latest('id')
            ->first();

        return $last ? $last->balance_after : 0;
    }

    /**
     * Award points to customer upon order placement or payment.
     */
    public function awardOrderPoints(Order $order): ?LoyaltyPoint
    {
        $userId = $order->user_id ?: ($order->customer_id ?? null);
        if (!$userId) {
            return null;
        }

        $points = (int) floor((float) $order->grand_total * self::POINTS_PER_DOLLAR_EARNED);
        if ($points <= 0) {
            return null;
        }

        $currentBalance = $this->getUserBalance($userId);
        $newBalance = $currentBalance + $points;

        return LoyaltyPoint::create([
            'tenant_id'     => $order->tenant_id,
            'user_id'       => $userId,
            'points_change' => $points,
            'balance_after' => $newBalance,
            'type'          => 'purchase',
            'order_id'      => $order->id,
            'description'   => "Earned {$points} points from Order #{$order->order_number}",
        ]);
    }

    /**
     * Redeem points for discount at checkout.
     */
    public function redeemPoints(int $userId, int $pointsToRedeem, ?int $orderId = null): array
    {
        $currentBalance = $this->getUserBalance($userId);
        if ($pointsToRedeem <= 0 || $pointsToRedeem > $currentBalance) {
            return [
                'status'  => 'error',
                'message' => 'Insufficient loyalty points balance.',
                'balance' => $currentBalance,
            ];
        }

        $discountValue = round($pointsToRedeem * self::VALUE_PER_POINT, 2);
        $newBalance = $currentBalance - $pointsToRedeem;

        LoyaltyPoint::create([
            'user_id'       => $userId,
            'points_change' => -$pointsToRedeem,
            'balance_after' => $newBalance,
            'type'          => 'redemption',
            'order_id'      => $orderId,
            'description'   => "Redeemed {$pointsToRedeem} points for " . money($discountValue) . " discount",
        ]);

        return [
            'status'         => 'success',
            'points_spent'   => $pointsToRedeem,
            'discount_value' => $discountValue,
            'balance_after'  => $newBalance,
            'message'        => "Successfully applied " . money($discountValue) . " loyalty discount.",
        ];
    }
}
