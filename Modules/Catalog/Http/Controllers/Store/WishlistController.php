<?php

namespace Modules\Catalog\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Services\WishlistService;

class WishlistController extends Controller
{
    /**
     * AJAX/Form toggle product in wishlist.
     */
    public function toggle(Request $request, WishlistService $service)
    {
        if (!auth()->check()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status'   => 'unauthenticated',
                    'message'  => 'Please sign in to save products to your wishlist.',
                    'redirect' => route('login'),
                ], 401);
            }
            return redirect()->route('login')->with('error', 'Please sign in to save items to your wishlist.');
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $result = $service->toggle(auth()->id(), (int) $request->product_id);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * View customer's saved wishlist items.
     */
    public function index(WishlistService $service)
    {
        $wishlistItems = $service->getUserWishlist(auth()->id());

        return view('catalog::customer.wishlist.index', compact('wishlistItems'));
    }

    /**
     * Move wishlist item to active shopping cart.
     */
    public function moveToCart(int $id, WishlistService $service)
    {
        $result = $service->moveToCart(auth()->id(), $id);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json($result);
        }

        return redirect()->route('account.wishlist')->with(
            $result['status'] === 'success' ? 'success' : 'error',
            $result['message']
        );
    }

    /**
     * Remove item from wishlist.
     */
    public function destroy(int $id, WishlistService $service)
    {
        $result = $service->toggle(auth()->id(), $id);

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json($result);
        }

        return back()->with('success', 'Product removed from your wishlist.');
    }
}
