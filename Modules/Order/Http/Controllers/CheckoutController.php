<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Cart\Services\CartService;
use Modules\Order\Models\Order;
use Modules\Order\Services\CheckoutService;
use Modules\Order\Services\PricingEngine;
use Modules\Payment\Models\CustomerPaymentMethod;
use Modules\Payment\Models\PaymentTransaction;
use Modules\Payment\Services\FinancialSettlementService;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
        protected PricingEngine $pricingEngine,
        protected \Modules\Order\Services\ShippingService $shippingService
    ) {
    }

    /**
     * Display the checkout screen.
     */
    public function index()
    {
        $cart = $this->cartService->getActiveCart();

        if ($cart->is_empty) {
            return redirect()->route('store.cart.index')
                ->with('error', 'Your shopping cart is empty. Please add items before checking out.');
        }

        $shippingMethods = $this->shippingService->getAvailableMethods($cart);
        $defaultMethod = $shippingMethods->first();
        $pricing = $this->pricingEngine->calculate($cart, $defaultMethod?->id);

        $savedPaymentMethods = Auth::check()
            ? CustomerPaymentMethod::where('user_id', Auth::id())->orderBy('is_default', 'desc')->latest('id')->get()
            : collect();

        return view('order::checkout.index', compact('cart', 'pricing', 'shippingMethods', 'defaultMethod', 'savedPaymentMethods'));
    }

    /**
     * Recalculate shipping cost and order total via AJAX.
     */
    public function calculateShipping(Request $request)
    {
        $cart = $this->cartService->getActiveCart();

        if ($cart->is_empty) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        $methodId = $request->input('shipping_method_id') ? (int) $request->input('shipping_method_id') : null;
        $pricing = $this->pricingEngine->calculate($cart, $methodId);

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Place order transactionally.
     */
    public function placeOrder(Request $request)
    {
        $cart = $this->cartService->getActiveCart();

        if ($cart->is_empty) {
            return redirect()->route('store.cart.index')
                ->with('error', 'Your shopping cart is empty.');
        }

        $validated = $request->validate([
            'customer_name'           => 'required|string|max:255',
            'customer_email'          => 'required|email|max:255',
            'customer_phone'          => 'required|string|max:30',
            'shipping_method_id'      => 'nullable|exists:shipping_methods,id',
            'shipping_address'        => 'required|array',
            'shipping_address.street'      => 'required|string|max:255',
            'shipping_address.city'        => 'required|string|max:100',
            'shipping_address.state'       => 'required|string|max:100',
            'shipping_address.country'     => 'required|string|max:100',
            'shipping_address.postal_code' => 'required|string|max:20',
            'payment_method'          => 'required|in:cod,stripe,paypal,bank_transfer,saved_card',
            'saved_payment_method_id' => 'required_if:payment_method,saved_card|nullable|integer',
            'notes'                   => 'nullable|string|max:500',
        ]);

        $savedCard = null;
        if ($validated['payment_method'] === 'saved_card') {
            if (!Auth::check()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'You must be logged in to pay with a saved payment method.'], 401);
                }
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'You must be logged in to pay with a saved payment method.');
            }

            $savedCard = CustomerPaymentMethod::where('user_id', Auth::id())
                ->findOrFail($validated['saved_payment_method_id']);
        }

        try {
            $order = $this->checkoutService->processCheckout(
                $cart,
                [
                    'name'             => $validated['customer_name'],
                    'email'            => $validated['customer_email'],
                    'phone'            => $validated['customer_phone'],
                    'shipping_address' => $validated['shipping_address'],
                ],
                $validated['payment_method'],
                $validated['notes'] ?? null,
                $validated['shipping_method_id'] ?? null
            );

            // If loyalty points redemption was selected
            $redeemedPoints = (int) $request->input('redeemed_points', 0);
            if ($request->has('use_loyalty_points') && $redeemedPoints > 0 && class_exists(\Modules\Rewards\Services\LoyaltyService::class)) {
                try {
                    $loyaltyService = app(\Modules\Rewards\Services\LoyaltyService::class);
                    $customerReward = $loyaltyService->getOrCreateCustomerReward($order->customer_email, $order->user_id);
                    $loyaltyService->redeemPoints($customerReward, $redeemedPoints, $order);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Could not redeem points: " . $e->getMessage());
                }
            }

            // Handle vaulted card transaction & instant settlement
            if ($savedCard) {
                $order->update(['payment_status' => 'paid']);

                $txRef = 'txn_vault_' . Str::random(16);
                PaymentTransaction::create([
                    'tenant_id'              => $order->tenant_id,
                    'order_id'               => $order->id,
                    'transaction_reference'  => $txRef,
                    'gateway'                => $savedCard->gateway ?? 'stripe',
                    'amount'                 => $order->grand_total,
                    'currency'               => $order->currency ?? 'USD',
                    'status'                 => 'successful',
                    'payment_method_details' => [
                        'brand'     => $savedCard->card_brand,
                        'last_four' => $savedCard->card_last_four,
                        'token'     => $savedCard->payment_method_token,
                        'exp_month' => $savedCard->card_exp_month,
                        'exp_year'  => $savedCard->card_exp_year,
                    ],
                    'payload'                => [
                        'payment_method_id' => $savedCard->id,
                        'type'              => 'vaulted_token',
                    ],
                ]);

                if (class_exists(FinancialSettlementService::class)) {
                    try {
                        app(FinancialSettlementService::class)->settleOrder($order, $txRef);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning("Could not settle order #{$order->order_number}: " . $e->getMessage());
                    }
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'status'       => 'success',
                        'message'      => "Order placed successfully using saved card ending in {$savedCard->card_last_four}.",
                        'order_number' => $order->order_number,
                        'order'        => $order,
                    ], 201);
                }

                return redirect()->route('store.order.confirmation', $order->order_number)
                    ->with('success', "Order placed successfully using your saved card ending in {$savedCard->card_last_four}!");
            }

            // If online payment gateway redirect required
            if (in_array($validated['payment_method'], ['stripe', 'paypal'])) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status'       => 'redirect',
                        'redirect_url' => route('store.payment.process', $order->order_number),
                        'order_number' => $order->order_number,
                    ]);
                }
                return redirect()->route('store.payment.process', $order->order_number);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'status'       => 'success',
                    'message'      => 'Order placed successfully.',
                    'order_number' => $order->order_number,
                    'order'        => $order,
                ], 201);
            }

            return redirect()->route('store.order.confirmation', $order->order_number)
                ->with('success', 'Order placed successfully!');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Checkout failed: ' . $e->getMessage()], 422);
            }
            return redirect()->back()
                ->withInput()
                ->with('error', 'Checkout failed: ' . $e->getMessage());
        }
    }

    /**
     * Display order placement confirmation.
     */
    public function confirmation($orderNumber)
    {
        $order = Order::with(['items.variant.product', 'shippingMethod', 'shipments'])->where('order_number', $orderNumber)->firstOrFail();

        return view('order::checkout.confirmation', compact('order'));
    }
}
