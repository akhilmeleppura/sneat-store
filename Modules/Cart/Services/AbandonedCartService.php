<?php

namespace Modules\Cart\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Modules\Cart\Models\AbandonedCartRecovery;
use Modules\Cart\Models\Cart;
use Modules\Order\Models\Coupon;
use Modules\Order\Models\Order;

class AbandonedCartService
{
    /**
     * Scan and detect abandoned carts.
     *
     * @param int $inactivityHours
     * @return int Count of newly detected abandoned carts
     */
    public function detectAbandonedCarts(int $inactivityHours = 1): int
    {
        $cutoff = Carbon::now()->subHours($inactivityHours)->toDateTimeString();

        $carts = Cart::withoutTenancy()
            ->with(['items.variant', 'user'])
            ->whereHas('items')
            ->where('updated_at', '<=', $cutoff)
            ->get();

        $count = 0;

        foreach ($carts as $cart) {
            // Determine customer email
            $email = $cart->customer_email ?? $cart->user?->email;
            if (! $email) {
                continue;
            }

            // Check if cart was already ordered or recovered
            $alreadyRecovered = AbandonedCartRecovery::withoutTenancy()
                ->where('cart_id', $cart->id)
                ->where('status', 'recovered')
                ->exists();

            if ($alreadyRecovered) {
                continue;
            }

            $alreadyOrdered = Order::withoutTenancy()
                ->where('created_at', '>', $cart->updated_at)
                ->where(function ($q) use ($cart, $email) {
                    $q->where('customer_email', $email);
                    if ($cart->user_id) {
                        $q->orWhere('user_id', $cart->user_id);
                    }
                })
                ->exists();

            if ($alreadyOrdered) {
                continue;
            }

            // Calculate cart subtotal
            $subtotal = $cart->items->sum(fn ($i) => (float) $i->unit_price * (int) $i->quantity);
            if ($subtotal <= 0) {
                continue;
            }

            $recovery = AbandonedCartRecovery::withoutTenancy()->firstOrCreate(
                ['cart_id' => $cart->id],
                [
                    'tenant_id'              => $cart->tenant_id,
                    'user_id'                => $cart->user_id,
                    'customer_email'         => $email,
                    'customer_phone'         => $cart->customer_phone,
                    'cart_subtotal'          => $subtotal,
                    'currency'               => $cart->currency ?? 'USD',
                    'recovery_token'         => AbandonedCartRecovery::generateToken(),
                    'status'                 => 'pending',
                    'recovery_discount_code' => 'COMEBACK10',
                    'items_count'            => $cart->items->sum('quantity'),
                ]
            );

            if ($recovery->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Send recovery email / notification.
     */
    public function sendRecoveryNotification(AbandonedCartRecovery $recovery): bool
    {
        if ($recovery->status === 'recovered') {
            return false;
        }

        // Update status progression
        $newStatus = $recovery->status === 'first_reminder_sent' ? 'second_reminder_sent' : 'first_reminder_sent';
        $recovery->update([
            'status'  => $newStatus,
            'sent_at' => now(),
        ]);

        Log::info("Abandoned Cart Recovery sent to {$recovery->customer_email} with token {$recovery->recovery_token} (URL: {$recovery->recovery_url})");

        return true;
    }

    /**
     * Restore cart session using recovery token.
     */
    public function restoreCartByToken(string $token): ?Cart
    {
        $recovery = AbandonedCartRecovery::withoutTenancy()->with('cart.items')->where('recovery_token', $token)->first();

        if (! $recovery || ! $recovery->cart) {
            return null;
        }

        $cart = $recovery->cart;

        // Restore cart token to session and cookie
        Session::put('cart_token', $cart->cart_token);
        Cookie::queue(cookie()->make('cart_token', $cart->cart_token, 60 * 24 * 30));

        // Apply recovery discount coupon if available
        if ($recovery->recovery_discount_code && empty($cart->coupon_code)) {
            $cart->coupon_code = $recovery->recovery_discount_code;
            $cart->save();
        }

        return $cart;
    }

    /**
     * Mark recovery record as recovered upon order placement.
     */
    public function markAsRecovered(Cart $cart, Order $order): void
    {
        $recovery = AbandonedCartRecovery::withoutTenancy()
            ->where('cart_id', $cart->id)
            ->whereIn('status', ['pending', 'first_reminder_sent', 'second_reminder_sent'])
            ->latest()
            ->first();

        if ($recovery) {
            $recovery->update([
                'status'             => 'recovered',
                'recovered_at'       => now(),
                'recovered_order_id' => $order->id,
            ]);
        }
    }

    /**
     * Calculate KPI metrics for admin overview.
     */
    public function getStats(): array
    {
        $totalAbandoned = AbandonedCartRecovery::count();
        $totalRecovered = AbandonedCartRecovery::where('status', 'recovered')->count();
        $recoverableAmount = (float) AbandonedCartRecovery::whereIn('status', ['pending', 'first_reminder_sent', 'second_reminder_sent'])->sum('cart_subtotal');
        $recoveredAmount = (float) AbandonedCartRecovery::where('status', 'recovered')->sum('cart_subtotal');
        $recoveryRate = $totalAbandoned > 0 ? round(($totalRecovered / $totalAbandoned) * 100, 1) : 0.0;

        return [
            'total_abandoned'    => $totalAbandoned,
            'total_recovered'    => $totalRecovered,
            'recoverable_amount' => $recoverableAmount,
            'recovered_amount'   => $recoveredAmount,
            'recovery_rate'      => $recoveryRate,
        ];
    }
}
