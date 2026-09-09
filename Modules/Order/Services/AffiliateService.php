<?php

namespace Modules\Order\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Context\Facades\Context;
use Modules\Order\Models\Affiliate;
use Modules\Order\Models\AffiliateReferral;
use Modules\Order\Models\Order;

class AffiliateService
{
    /**
     * Get or create an affiliate profile for a user.
     */
    public function getOrCreateAffiliateForUser(User $user, float $defaultCommissionRate = 10.00): Affiliate
    {
        $tenantId = Context::tenantId() ?? 1;

        $affiliate = Affiliate::withoutTenancy()->where('user_id', $user->id)->first();

        if (! $affiliate) {
            $baseCode = strtoupper(Str::slug(explode('@', $user->email)[0]));
            $code = substr($baseCode, 0, 8) . rand(100, 999);

            while (Affiliate::withoutTenancy()->where('affiliate_code', $code)->exists()) {
                $code = substr($baseCode, 0, 6) . rand(1000, 9999);
            }

            $affiliate = Affiliate::create([
                'tenant_id'        => $tenantId,
                'user_id'          => $user->id,
                'affiliate_code'   => $code,
                'commission_rate'  => $defaultCommissionRate,
                'total_earnings'   => 0.00,
                'pending_earnings' => 0.00,
                'paid_earnings'    => 0.00,
                'status'           => 'active',
                'payout_method'    => 'paypal',
                'payout_account'   => $user->email,
            ]);
        }

        return $affiliate;
    }

    /**
     * Look up affiliate by code.
     */
    public function getAffiliateByCode(string $code): ?Affiliate
    {
        return Affiliate::withoutTenancy()->where('affiliate_code', strtoupper(trim($code)))->active()->first();
    }

    /**
     * Record referral conversion for an order.
     */
    public function recordReferralConversion(Order $order, string $affiliateCode): ?AffiliateReferral
    {
        $affiliate = $this->getAffiliateByCode($affiliateCode);
        if (! $affiliate) {
            return null;
        }

        // Prevent self-referral
        if ($order->user_id && $order->user_id === $affiliate->user_id) {
            return null;
        }

        // Check if referral already recorded for this order
        $existing = AffiliateReferral::withoutTenancy()->where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $orderSubtotal = (float) $order->subtotal;
        $commissionAmount = $affiliate->calculateCommission($orderSubtotal);

        if ($commissionAmount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($affiliate, $order, $orderSubtotal, $commissionAmount) {
            $referral = AffiliateReferral::create([
                'tenant_id'         => $order->tenant_id ?? $affiliate->tenant_id,
                'affiliate_id'      => $affiliate->id,
                'order_id'          => $order->id,
                'customer_email'    => $order->customer_email,
                'order_amount'      => $orderSubtotal,
                'commission_rate'   => $affiliate->commission_rate,
                'commission_amount' => $commissionAmount,
                'status'            => 'pending',
            ]);

            $affiliate->increment('total_earnings', $commissionAmount);
            $affiliate->increment('pending_earnings', $commissionAmount);

            return $referral;
        });
    }

    /**
     * Approve and mark referral as paid.
     */
    public function markReferralPaid(AffiliateReferral $referral): void
    {
        if ($referral->status === 'paid') {
            return;
        }

        DB::transaction(function () use ($referral) {
            $affiliate = $referral->affiliate;

            $referral->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            $amount = (float) $referral->commission_amount;
            if ($affiliate) {
                $affiliate->pending_earnings = max(0, $affiliate->pending_earnings - $amount);
                $affiliate->paid_earnings += $amount;
                $affiliate->save();
            }
        });
    }

    /**
     * Get platform overview metrics for admin.
     */
    public function getAdminStats(): array
    {
        $totalAffiliates = Affiliate::withoutTenancy()->count();
        $referredSales = (float) AffiliateReferral::withoutTenancy()->sum('order_amount');
        $pendingCommissions = (float) AffiliateReferral::withoutTenancy()->whereIn('status', ['pending', 'approved'])->sum('commission_amount');
        $paidCommissions = (float) AffiliateReferral::withoutTenancy()->where('status', 'paid')->sum('commission_amount');

        return [
            'total_affiliates'    => $totalAffiliates,
            'referred_sales'      => $referredSales,
            'pending_commissions' => $pendingCommissions,
            'paid_commissions'    => $paidCommissions,
        ];
    }
}
