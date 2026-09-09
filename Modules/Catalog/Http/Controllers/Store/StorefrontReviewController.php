<?php

namespace Modules\Catalog\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\ReviewService;

class StorefrontReviewController extends Controller
{
    /**
     * Submit a customer review for a product.
     */
    public function store(Request $request, string $slug, ReviewService $reviewService)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'title'   => 'nullable|string|max:255',
            'comment' => 'required|string|min:5|max:2000',
        ]);

        $userId = Auth::id();

        $reviewService->submitReview(
            $product->id,
            $userId,
            (int) $validated['rating'],
            $validated['title'] ?? null,
            $validated['comment']
        );

        return redirect()->route('storefront.product.show', $product->slug)
            ->with('success', 'Thank you! Your review has been submitted successfully.');
    }
}
