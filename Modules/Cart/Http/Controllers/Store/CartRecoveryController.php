<?php

namespace Modules\Cart\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Cart\Services\AbandonedCartService;

class CartRecoveryController extends Controller
{
    public function __construct(protected AbandonedCartService $recoveryService)
    {
    }

    /**
     * Restore customer's abandoned cart and redirect to checkout.
     */
    public function recover(Request $request, string $token)
    {
        $cart = $this->recoveryService->restoreCartByToken($token);

        if (! $cart) {
            return redirect()->route('store.cart.index')
                ->with('error', 'This recovery link has expired or the cart was already completed.');
        }

        return redirect()->route('store.checkout.index')
            ->with('success', 'Welcome back! We restored your items and applied your exclusive recovery discount.');
    }
}
