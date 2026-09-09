<?php

namespace Modules\Order\Services;

use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;

class PricingEngine
{
    /**
     * Compute financial totals for a given Cart with zero client trust.
     */
    public function calculate(Cart $cart, ?int $shippingMethodId = null): array
    {
        $cart->loadMissing(['items.variant.product']);

        $calculatedItems = [];
        $subtotal = 0.00;

        foreach ($cart->items as $item) {
            $variant = $item->variant;
            if (! $variant) {
                continue;
            }

            // Fresh server-side price from database
            $unitPrice = (float) $variant->price;
            $quantity = (int) $item->quantity;
            $lineSubtotal = round($unitPrice * $quantity, 2);
            $discount = 0.00;
            $lineTotal = max(0, $lineSubtotal - $discount);

            $calculatedItems[] = [
                'cart_item_id'       => $item->id,
                'product_id'         => $variant->product_id,
                'product_variant_id' => $variant->id,
                'product_name'       => $variant->product->name ?? 'Product',
                'variant_sku'        => $variant->sku,
                'unit_price'         => $unitPrice,
                'quantity'           => $quantity,
                'discount_amount'    => $discount,
                'line_total'         => $lineTotal,
            ];

            $subtotal += $lineTotal;
        }

        // Dynamic coupon and promotions calculation
        $discountAmount = 0.00;
        $appliedCoupon = null;
        if (! empty($cart->coupon_code)) {
            $promotionService = app(PromotionService::class);
            $validation = $promotionService->validateCoupon($cart->coupon_code, $cart);
            if ($validation['valid']) {
                $discountAmount = (float) $validation['discount'];
                $appliedCoupon = $validation['coupon'];
            }
        }

        $taxableBase = max(0, $subtotal - $discountAmount);
        $taxRate = (float) config('order.default_tax_rate', 10.00);
        $taxAmount = round($taxableBase * ($taxRate / 100), 2);

        // Shipping fee calculation
        $selectedMethod = null;
        if ($shippingMethodId) {
            $selectedMethod = \Modules\Order\Models\ShippingMethod::find($shippingMethodId);
        }

        if ($selectedMethod) {
            $shippingFee = app(ShippingService::class)->calculateCost($selectedMethod, $cart, $subtotal);
        } else {
            $shippingFee = (float) config('order.default_shipping_fee', 0.00);
            $freeShippingThreshold = (float) config('order.free_shipping_threshold', 100.00);
            if ($subtotal >= $freeShippingThreshold) {
                $shippingFee = 0.00;
            }
        }

        $grandTotal = round(max(0, $taxableBase + $taxAmount + $shippingFee), 2);

        return [
            'items'              => $calculatedItems,
            'subtotal'           => round($subtotal, 2),
            'discount_amount'    => $discountAmount,
            'coupon'             => $appliedCoupon,
            'coupon_code'        => $appliedCoupon ? $appliedCoupon->code : null,
            'tax_rate'           => $taxRate,
            'tax_amount'         => $taxAmount,
            'shipping_method'    => $selectedMethod,
            'shipping_method_id' => $selectedMethod?->id,
            'shipping_amount'    => $shippingFee,
            'grand_total'        => $grandTotal,
            'currency'           => $cart->currency ?: 'USD',
        ];
    }
}
