<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductReview;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class ReviewService
{
    /**
     * Submit a customer review for a product with automatic verified buyer detection.
     */
    public function submitReview(
        int $productId,
        int $userId,
        int $rating,
        ?string $title,
        string $comment
    ): ProductReview {
        $product = Product::findOrFail($productId);
        $rating = max(1, min(5, (int) $rating));

        // Check if user has purchased this product in an active/completed order
        $orderItem = OrderItem::where('product_id', $productId)
            ->whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->whereNotIn('status', ['cancelled']);
            })
            ->latest()
            ->first();

        $isVerifiedBuyer = ! is_null($orderItem);
        $orderId = $orderItem ? $orderItem->order_id : null;

        $review = ProductReview::create([
            'tenant_id'          => $product->tenant_id,
            'product_id'         => $product->id,
            'user_id'            => $userId,
            'order_id'           => $orderId,
            'vendor_id'          => $product->vendor_id,
            'rating'             => $rating,
            'title'              => $title ? trim($title) : null,
            'comment'            => trim($comment),
            'is_verified_buyer'  => $isVerifiedBuyer,
            'is_approved'        => true, // Auto-approved by default
        ]);

        $this->recalculateProductRating($productId);

        return $review;
    }

    /**
     * Calculate aggregate rating statistics and star distribution breakdown.
     */
    public function getRatingBreakdown(int $productId): array
    {
        $approvedQuery = ProductReview::where('product_id', $productId)->where('is_approved', true);
        $totalCount = (clone $approvedQuery)->count();
        $avgRating = (float) ((clone $approvedQuery)->avg('rating') ?: 0.0);

        $counts = (clone $approvedQuery)
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $breakdown = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = (int) ($counts[$star] ?? 0);
            $percentage = $totalCount > 0 ? round(($count / $totalCount) * 100) : 0;
            $breakdown[$star] = [
                'count'      => $count,
                'percentage' => $percentage,
            ];
        }

        return [
            'average'     => round($avgRating, 1),
            'total_count' => $totalCount,
            'breakdown'   => $breakdown,
        ];
    }

    /**
     * Toggle approval visibility status for moderation.
     */
    public function toggleApproval(int $reviewId): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->is_approved = ! $review->is_approved;
        $review->save();

        $this->recalculateProductRating($review->product_id);

        return $review;
    }

    /**
     * Attach an official merchant or admin reply to a review.
     */
    public function replyToReview(int $reviewId, string $replyText): ProductReview
    {
        $review = ProductReview::findOrFail($reviewId);
        $review->admin_reply = trim($replyText);
        $review->replied_at = now();
        $review->save();

        return $review;
    }

    /**
     * Atomically recalculate and cache product average rating and count.
     */
    public function recalculateProductRating(int $productId): void
    {
        $approvedQuery = ProductReview::where('product_id', $productId)->where('is_approved', true);
        $count = (clone $approvedQuery)->count();
        $avg = (float) ((clone $approvedQuery)->avg('rating') ?: 0.00);

        Product::where('id', $productId)->update([
            'rating_cache' => round($avg, 2),
            'rating_count' => $count,
        ]);
    }
}
