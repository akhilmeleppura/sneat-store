<?php

namespace Modules\Order\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cart\Models\Cart;
use Modules\Cart\Services\CartService;
use Modules\Context\Facades\Context;
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class CheckoutService
{
    public function __construct(protected PricingEngine $pricingEngine)
    {
    }

    /**
     * Process checkout transactionally: calculate totals, reserve stock, generate order, empty cart.
     *
     * @throws Exception
     */
    public function processCheckout(
        Cart $cart,
        array $customerData,
        string $paymentMethod = 'cod',
        ?string $notes = null,
        ?int $shippingMethodId = null
    ): Order {
        if ($cart->is_empty) {
            throw new Exception('Cannot checkout an empty cart.');
        }

        // 1. Recalculate totals via PricingEngine
        $calculation = $this->pricingEngine->calculate($cart, $shippingMethodId);

        if (empty($calculation['items'])) {
            throw new Exception('Cart contains no valid items for checkout.');
        }

        $tenantId = $cart->tenant_id ?? (Context::tenantId() ?? 1);
        $storeId  = $cart->store_id ?? (Context::storeId() ?? 1);
        $branchId = Context::branchId() ?? 1;

        $inventoryService = app(InventoryService::class);

        return DB::transaction(function () use ($cart, $calculation, $customerData, $paymentMethod, $notes, $shippingMethodId, $tenantId, $storeId, $branchId, $inventoryService) {
            $orderNumber = config('order.prefix', 'ORD-') . date('Ymd') . '-' . strtoupper(Str::random(5));

            // 2. Atomically reserve inventory for every item
            foreach ($calculation['items'] as $item) {
                $reserved = $inventoryService->reserveStock(
                    $item['product_variant_id'],
                    $branchId,
                    $item['quantity'],
                    $orderNumber
                );

                if (! $reserved) {
                    throw new Exception("Unable to reserve inventory for '{$item['product_name']}'. Stock level may have changed.");
                }
            }

            // 3. Resolve Active Currency Context
            $currencyService = app(\Modules\Context\Services\CurrencyService::class);
            $activeCurrency = $currencyService->getCurrentCurrency();
            $baseCurrency = $currencyService->getDefaultCurrency();
            $exchangeRate = (float) $activeCurrency->exchange_rate;

            $baseGrandTotal = (float) $calculation['grand_total'];
            $baseSubtotal   = (float) $calculation['subtotal'];
            $baseDiscount   = (float) $calculation['discount_amount'];
            $baseTax        = (float) $calculation['tax_amount'];
            $baseShipping   = (float) $calculation['shipping_amount'];

            $checkoutCurrency = $activeCurrency->code;
            $checkoutSubtotal = $currencyService->convert($baseSubtotal, $baseCurrency->code, $checkoutCurrency);
            $checkoutDiscount = $currencyService->convert($baseDiscount, $baseCurrency->code, $checkoutCurrency);
            $checkoutTax      = $currencyService->convert($baseTax, $baseCurrency->code, $checkoutCurrency);
            $checkoutShipping = $currencyService->convert($baseShipping, $baseCurrency->code, $checkoutCurrency);
            $checkoutGrandTotal = $currencyService->convert($baseGrandTotal, $baseCurrency->code, $checkoutCurrency);

            $shippingMethod = $calculation['shipping_method'] ?? null;
            if (! $shippingMethod && $shippingMethodId) {
                $shippingMethod = \Modules\Order\Models\ShippingMethod::find($shippingMethodId);
            }
            $estimatedDeliveryDate = $shippingMethod?->calculateEstimatedDeliveryDate()?->toDateString();

            // 4. Create Order
            $order = Order::create([
                'tenant_id'               => $tenantId,
                'store_id'                => $storeId,
                'tenant_branch_id'        => $branchId,
                'user_id'                 => Auth::id() ?? $cart->user_id,
                'order_number'            => $orderNumber,
                'customer_name'           => $customerData['customer_name'] ?? $customerData['name'] ?? 'Guest Customer',
                'customer_email'          => $customerData['customer_email'] ?? $customerData['email'] ?? 'guest@sneat.test',
                'customer_phone'          => $customerData['customer_phone'] ?? $customerData['phone'] ?? null,
                'shipping_address'        => $customerData['shipping_address'] ?? null,
                'billing_address'         => $customerData['billing_address'] ?? ($customerData['shipping_address'] ?? null),
                'subtotal'                => $checkoutSubtotal,
                'discount_amount'         => $checkoutDiscount,
                'tax_amount'              => $checkoutTax,
                'shipping_amount'         => $checkoutShipping,
                'grand_total'             => $checkoutGrandTotal,
                'currency'                => $checkoutCurrency,
                'exchange_rate'           => $exchangeRate,
                'base_currency'           => $baseCurrency->code,
                'base_grand_total'        => $baseGrandTotal,
                'status'                  => 'pending',
                'payment_status'          => ($paymentMethod === 'cod') ? 'unpaid' : 'paid',
                'payment_method'          => $paymentMethod,
                'fulfillment_status'      => 'unfulfilled',
                'shipping_method_id'      => $shippingMethod?->id,
                'estimated_delivery_date' => $estimatedDeliveryDate,
                'coupon_code'             => $calculation['coupon_code'] ?? ($cart->coupon_code ? strtoupper($cart->coupon_code) : null),
                'notes'                   => $notes,
            ]);

            // 5. Create Order Items
            foreach ($calculation['items'] as $item) {
                $itemUnitPrice = $currencyService->convert($item['unit_price'], $baseCurrency->code, $checkoutCurrency);
                $itemLineTotal = $currencyService->convert($item['line_total'], $baseCurrency->code, $checkoutCurrency);
                $itemDiscount  = $currencyService->convert($item['discount_amount'] ?? 0, $baseCurrency->code, $checkoutCurrency);

                OrderItem::create([
                    'order_id'           => $order->id,
                    'product_id'         => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'product_name'       => $item['product_name'],
                    'variant_sku'        => $item['variant_sku'],
                    'unit_price'         => $itemUnitPrice,
                    'quantity'           => $item['quantity'],
                    'tax_amount'         => 0.00,
                    'discount_amount'    => $itemDiscount,
                    'line_total'         => $itemLineTotal,
                ]);
            }

            // 6. Record Coupon Usage
            if (! empty($calculation['coupon']) || ! empty($cart->coupon_code)) {
                $coupon = $calculation['coupon'] ?? \Modules\Order\Models\Coupon::where('code', strtoupper($cart->coupon_code))->first();
                if ($coupon) {
                    app(\Modules\Order\Services\PromotionService::class)->recordUsage(
                        $coupon,
                        $order,
                        Auth::user() ?? ($order->user_id ? \App\Models\User::find($order->user_id) : null),
                        $order->customer_email,
                        $checkoutDiscount
                    );
                }
            }

            // 7. Create Initial Fulfillment Shipment
            if ($shippingMethod || ! empty($customerData['shipping_address'])) {
                app(\Modules\Order\Services\ShippingService::class)->createShipment($order, [
                    'shipping_method_id'    => $shippingMethod?->id,
                    'carrier'               => $shippingMethod?->carrier,
                    'estimated_delivery_at' => $estimatedDeliveryDate,
                ]);
            }

            // 8. Empty Cart
            app(CartService::class)->clearCart();

            // 9. Award Loyalty Points if Rewards module is available
            if (class_exists(\Modules\Rewards\Services\LoyaltyService::class)) {
                try {
                    app(\Modules\Rewards\Services\LoyaltyService::class)->awardPointsForOrder($order);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Could not award loyalty points: " . $e->getMessage());
                }
            }

            // 10. Mark Abandoned Cart as recovered if applicable
            if (class_exists(\Modules\Cart\Services\AbandonedCartService::class)) {
                try {
                    app(\Modules\Cart\Services\AbandonedCartService::class)->markAsRecovered($cart, $order);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Could not mark abandoned cart recovered: " . $e->getMessage());
                }
            }

            // 11. Attribute Affiliate Referral if cookie or parameter is present
            $referralCode = request()->cookie('sneat_referral_code') ?? request()->input('referral_code') ?? session('sneat_referral_code');
            if ($referralCode && class_exists(\Modules\Order\Services\AffiliateService::class)) {
                try {
                    app(\Modules\Order\Services\AffiliateService::class)->recordReferralConversion($order, $referralCode);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Could not record affiliate referral: " . $e->getMessage());
                }
            }

            // 12. Dispatch Order Placed Event
            event(new \Modules\Order\Events\OrderPlacedEvent($order));

            return $order;
        });
    }
}
