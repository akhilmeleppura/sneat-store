<?php

namespace Modules\Rewards\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Rewards\Services\LoyaltyService;

class CustomerRewardController extends Controller
{
    public function __construct(protected LoyaltyService $loyaltyService)
    {
    }

    /**
     * Check loyalty balance and rewards tier for customer.
     */
    public function balance(Request $request)
    {
        $user = auth()->user();
        $email = $user ? $user->email : $request->query('email');

        if (! $email) {
            return response()->json([
                'success' => false,
                'message' => 'Customer email required to check loyalty points.',
            ], 400);
        }

        $customerReward = $this->loyaltyService->getOrCreateCustomerReward($email, $user?->id);
        $campaign = $this->loyaltyService->getActiveCampaign();

        $redeemRate = $campaign ? (float) $campaign->redeem_rate : 0.01;
        $maxDiscount = round($customerReward->current_points * $redeemRate, 2);

        return response()->json([
            'success' => true,
            'email' => $customerReward->customer_email,
            'current_points' => $customerReward->current_points,
            'lifetime_points' => $customerReward->lifetime_points,
            'tier' => $customerReward->tier,
            'redeem_rate' => $redeemRate,
            'max_discount' => $maxDiscount,
            'min_points_to_redeem' => $campaign ? $campaign->min_points_to_redeem : 50,
        ]);
    }

    /**
     * Get recent loyalty transaction history.
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $email = $user ? $user->email : $request->query('email');

        if (! $email) {
            return response()->json(['success' => false, 'message' => 'Email required'], 400);
        }

        $customerReward = $this->loyaltyService->getOrCreateCustomerReward($email, $user?->id);
        $transactions = $customerReward->transactions()->limit(20)->get();

        return response()->json([
            'success' => true,
            'current_points' => $customerReward->current_points,
            'tier' => $customerReward->tier,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Calculate discount for requested points.
     */
    public function estimate(Request $request)
    {
        $points = (int) $request->input('points', 0);
        $campaign = $this->loyaltyService->getActiveCampaign();
        $rate = $campaign ? (float) $campaign->redeem_rate : 0.01;

        $discount = round($points * $rate, 2);

        return response()->json([
            'success' => true,
            'points' => $points,
            'discount' => $discount,
        ]);
    }
}
