<?php

namespace Modules\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Cart\Services\CartService;
use Modules\Order\Services\PricingEngine;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected PricingEngine $pricingEngine
    ) {
    }

    /**
     * Display the shopping cart.
     */
    public function index()
    {
        $cart = $this->cartService->getActiveCart();
        $pricing = $this->pricingEngine->calculate($cart);

        return view('cart::index', compact('cart', 'pricing'));
    }

    /**
     * Add item to the shopping cart.
     */
    public function add(Request $request)
    {
        if (! $request->has('variant_id') && $request->has('product_variant_id')) {
            $request->merge(['variant_id' => $request->input('product_variant_id')]);
        }

        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:product_variants,id',
            'quantity'   => 'nullable|integer|min:1',
        ]);

        try {
            $qty = $validated['quantity'] ?? 1;
            $this->cartService->addItem((int) $validated['variant_id'], $qty);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item added to shopping cart!',
                    'cart'    => $this->cartService->getActiveCart(),
                ]);
            }

            return redirect()->route('store.cart.index')
                ->with('success', 'Item successfully added to cart.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update item quantity in the shopping cart.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'item_id'  => 'required|integer|exists:cart_items,id',
            'quantity' => 'required|integer|min:0',
        ]);

        try {
            $this->cartService->updateQuantity((int) $validated['item_id'], (int) $validated['quantity']);

            if ($request->wantsJson()) {
                return response()->json(['success' => true]);
            }

            return redirect()->route('store.cart.index')
                ->with('success', 'Cart updated.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->route('store.cart.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove item from cart.
     */
    public function remove($id)
    {
        $this->cartService->removeItem((int) $id);

        return redirect()->route('store.cart.index')
            ->with('success', 'Item removed from cart.');
    }

    /**
     * Apply coupon discount to active cart with validation.
     */
    public function applyCoupon(Request $request)
    {
        $validated = $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        $cart = $this->cartService->getActiveCart();
        $promotionService = app(\Modules\Order\Services\PromotionService::class);
        $result = $promotionService->applyCoupon(
            $cart,
            $validated['coupon_code'],
            \Illuminate\Support\Facades\Auth::user()
        );

        if (! $result['valid']) {
            return redirect()->route('store.cart.index')
                ->with('error', $result['error'] ?? 'Invalid coupon code.');
        }

        $discountFormatted = function_exists('money') ? money($result['discount']) : '$' . number_format($result['discount'], 2);

        return redirect()->route('store.cart.index')
            ->with('success', "Coupon '{$result['coupon']->code}' applied! You saved {$discountFormatted}.");
    }

    /**
     * Remove applied coupon from active cart.
     */
    public function removeCoupon()
    {
        $cart = $this->cartService->getActiveCart();
        app(\Modules\Order\Services\PromotionService::class)->removeCoupon($cart);

        return redirect()->route('store.cart.index')
            ->with('success', 'Coupon removed from cart.');
    }
}
