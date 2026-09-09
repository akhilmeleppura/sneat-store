<?php

namespace Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\ProductReview;
use Modules\Catalog\Services\ReviewService;

class AdminReviewController extends Controller
{
    /**
     * Display a listing of product reviews with moderation tools and KPIs.
     */
    public function index(Request $request)
    {
        $query = ProductReview::with(['product', 'user', 'vendor'])->latest();

        if ($request->filled('status')) {
            if ($request->status === 'approved') {
                $query->where('is_approved', true);
            } elseif ($request->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }

        if ($request->filled('verified')) {
            $query->where('is_verified_buyer', $request->boolean('verified'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('comment', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $reviews = $query->paginate(15)->withQueryString();

        // Calculate Overview KPIs
        $totalReviews = ProductReview::count();
        $approvedReviews = ProductReview::where('is_approved', true)->count();
        $verifiedBuyerReviews = ProductReview::where('is_verified_buyer', true)->count();
        $averagePlatformRating = round((float) (ProductReview::where('is_approved', true)->avg('rating') ?: 0.0), 1);

        return view('catalog::reviews.index', compact(
            'reviews',
            'totalReviews',
            'approvedReviews',
            'verifiedBuyerReviews',
            'averagePlatformRating'
        ));
    }

    /**
     * Toggle visibility approval of a review.
     */
    public function toggleApproval(int $id, ReviewService $reviewService)
    {
        $review = $reviewService->toggleApproval($id);
        $status = $review->is_approved ? 'approved & visible' : 'hidden & pending';

        return redirect()->back()
            ->with('success', "Review #{$id} is now {$status}.");
    }

    /**
     * Submit an administrative / merchant reply.
     */
    public function reply(Request $request, int $id, ReviewService $reviewService)
    {
        $validated = $request->validate([
            'admin_reply' => 'required|string|max:2000',
        ]);

        $reviewService->replyToReview($id, $validated['admin_reply']);

        return redirect()->back()
            ->with('success', "Reply submitted for Review #{$id}.");
    }

    /**
     * Remove the specified review.
     */
    public function destroy(int $id, ReviewService $reviewService)
    {
        $review = ProductReview::findOrFail($id);
        $productId = $review->product_id;
        $review->delete();

        $reviewService->recalculateProductRating($productId);

        return redirect()->back()
            ->with('success', "Review #{$id} deleted successfully.");
    }
}
