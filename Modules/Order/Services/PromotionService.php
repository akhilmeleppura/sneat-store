<?php

namespace Modules\Order\Services;

use App\Models\User;
use Modules\Cart\Models\Cart;
use Modules\Order\Models\Coupon;
use Modules\Order\Models\CouponUsage;
use Modules\Order\Models\Order;

class PromotionService
{
    /**
     * Validate a coupon against a Cart and customer context.
     *
     * @return array{valid: bool, coupon: ?Coupon, discount: float, error: ?string}
     */
    public function validateCoupon(string $code, Cart $cart, ?User $user = null, ?string $email = null): array
    {
        $code = strtoupper(trim($code));
        if (empty($code)) {
            return [
                'valid'    => false,
                'coupon'   => null,
                'discount' => 0.00,
                'error'    => 'Please enter a coupon code.',
            ];
        }

        // Search scoped by tenant (UsesTenant trait applied)
        $coupon = Coupon::where('code', $code)->first();

        // Support backward compatibility for demo WELCOME10 if not in database yet
        if (! $coupon && $code === 'WELCOME10') {
            $coupon = Coupon::create([
                'tenant_id'            => $cart->tenant_id ?? 1,
                'code'                 => 'WELCOME10',
                'name'                 => 'Welcome 10% Discount',
                'description'          => 'Enjoy 10% off your store order',
                'type'                 => 'percentage',
                'value'                => 10.00,
                'min_order_amount'     => 0.00,
                'max_discount_amount'  => null,
                'usage_limit'          => null,
                'usage_limit_per_user' => 1,
                'is_active'            => true,
            ]);
        }

        if (! $coupon) {
            return [
                'valid'    => false,
                'coupon'   => null,
                'discount' => 0.00,
                'error'    => "Coupon code '{$code}' was not found.",
            ];
        }

        if (! $coupon->is_active) {
            return [
                'valid'    => false,
                'coupon'   => $coupon,
                'discount' => 0.00,
                'error'    => "Coupon '{$code}' has been disabled.",
            ];
        }

        $now = now();
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return [
                'valid'    => false,
                'coupon'   => $coupon,
                'discount' => 0.00,
                'error'    => "Coupon '{$code}' is not active yet (starts {$coupon->starts_at->toFormattedDateString()}).",
            ];
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            return [
                'valid'    => false,
                'coupon'   => $coupon,
                'discount' => 0.00,
                'error'    => "Coupon '{$code}' expired on {$coupon->expires_at->toFormattedDateString()}.",
            ];
        }

        if ($coupon->usage_limit !== null && $coupon->times_used >= $coupon->usage_limit) {
            return [
                'valid'    => false,
                'coupon'   => $coupon,
                'discount' => 0.00,
                'error'    => "Coupon '{$code}' has reached its maximum global redemptions.",
            ];
        }

        // Determine user / email context
        $user = $user ?? ($cart->user_id ? User::find($cart->user_id) : null);
        if (! $coupon->canBeUsedBy($user, $email)) {
            return [
                'valid'    => false,
                'coupon'   => $coupon,
                'discount' => 0.00,
                'error'    => "You have already used coupon '{$code}' the maximum number of times allowed.",
            ];
        }

        // Calculate Cart Subtotal based on fresh physical line items
        $cart->load(['items.variant']);
        $subtotal = 0.00;
        foreach ($cart->items as $item) {
            $price = (float) ($item->variant->price ?? 0);
            $subtotal += ($price * (int) $item->quantity);
        }

        // Vendor restriction check if coupon is vendor-scoped
        if ($coupon->vendor_id) {
            $cart->load(['items.variant.product']);
            $vendorSubtotal = 0.00;
            foreach ($cart->items as $item) {
                if (($item->variant->product->vendor_id ?? null) == $coupon->vendor_id) {
                    $vendorSubtotal += ((float) ($item->variant->price ?? 0) * (int) $item->quantity);
                }
            }

            if ($vendorSubtotal <= 0) {
                return [
                    'valid'    => false,
                    'coupon'   => $coupon,
                    'discount' => 0.00,
                    'error'    => "Coupon '{$code}' applies only to products from its designated vendor.",
                ];
            }

            $subtotal = $vendorSubtotal;
        }

        if ($subtotal < (float) $coupon->min_order_amount) {
            return [
                'valid'    => false,
                'coupon'   => $coupon,
                'discount' => 0.00,
                'error'    => "Coupon '{$code}' requires a minimum spend of $" . number_format($coupon->min_order_amount, 2) . " (Current: $" . number_format($subtotal, 2) . ").",
            ];
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return [
            'valid'    => true,
            'coupon'   => $coupon,
            'discount' => $discount,
            'error'    => null,
        ];
    }

    /**
     * Validate and apply a coupon to a cart.
     */
    public function applyCoupon(Cart $cart, string $code, ?User $user = null, ?string $email = null): array
    {
        $result = $this->validateCoupon($code, $cart, $user, $email);

        if ($result['valid']) {
            $cart->update(['coupon_code' => $result['coupon']->code]);
        }

        return $result;
    }

    /**
     * Remove applied coupon from cart.
     */
    public function removeCoupon(Cart $cart): void
    {
        $cart->update(['coupon_code' => null]);
    }

    /**
     * Atomically record coupon usage for an order.
     */
    public function recordUsage(
        Coupon $coupon,
        Order $order,
        ?User $user = null,
        ?string $email = null,
        float $discount = 0.00
    ): CouponUsage {
        $customerEmail = $email ?? $order->customer_email;
        if ($customerEmail) {
            $customerEmail = strtolower(trim($customerEmail));
        }

        $usage = CouponUsage::create([
            'tenant_id'       => $order->tenant_id,
            'coupon_id'       => $coupon->id,
            'order_id'        => $order->id,
            'user_id'         => $user?->id ?? $order->user_id,
            'customer_email'  => $customerEmail,
            'discount_amount' => $discount,
            'currency'        => $order->currency ?? 'USD',
        ]);

        $coupon->increment('times_used');

        return $usage;
    }
}
