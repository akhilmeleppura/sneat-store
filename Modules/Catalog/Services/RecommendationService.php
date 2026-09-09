<?php

namespace Modules\Catalog\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Modules\Catalog\Models\Product;

class RecommendationService
{
    protected const RECENTLY_VIEWED_KEY = 'store_recently_viewed';

    /**
     * Record a product as recently viewed in session.
     */
    public function trackRecentlyViewed(int $productId): void
    {
        $viewed = Session::get(self::RECENTLY_VIEWED_KEY, []);
        $viewed = array_diff($viewed, [$productId]);
        array_unshift($viewed, $productId);
        $viewed = array_slice($viewed, 0, 10);
        Session::put(self::RECENTLY_VIEWED_KEY, $viewed);
    }

    /**
     * Retrieve products recently viewed by the user.
     */
    public function getRecentlyViewed(int $limit = 6, ?int $excludeId = null): Collection
    {
        $ids = Session::get(self::RECENTLY_VIEWED_KEY, []);
        if ($excludeId) {
            $ids = array_diff($ids, [$excludeId]);
        }

        if (empty($ids)) {
            return collect();
        }

        $ids = array_slice($ids, 0, $limit);
        $products = Product::whereIn('id', $ids)
            ->where('status', 'published')
            ->with(['category', 'brand'])
            ->get();

        return $products->sortBy(function ($model) use ($ids) {
            return array_search($model->id, $ids);
        })->values();
    }

    /**
     * Recommendations: "Customers Also Bought" based on historical order baskets.
     */
    public function getCustomersAlsoBought(int $productId, int $limit = 4): Collection
    {
        // 1. Find orders containing this product
        $orderIds = DB::table('order_items')
            ->where('product_id', $productId)
            ->pluck('order_id');

        if ($orderIds->isEmpty()) {
            return $this->getRelatedProducts(Product::find($productId), $limit);
        }

        // 2. Find other products in these orders
        $coOccurredProductIds = DB::table('order_items')
            ->whereIn('order_id', $orderIds)
            ->where('product_id', '!=', $productId)
            ->select('product_id', DB::raw('COUNT(*) as frequency'))
            ->groupBy('product_id')
            ->orderByDesc('frequency')
            ->limit($limit)
            ->pluck('product_id');

        if ($coOccurredProductIds->isEmpty()) {
            return $this->getRelatedProducts(Product::find($productId), $limit);
        }

        return Product::whereIn('id', $coOccurredProductIds)
            ->where('status', 'published')
            ->with(['category', 'brand'])
            ->get();
    }

    /**
     * Fallback or category-based related products.
     */
    public function getRelatedProducts(?Product $product, int $limit = 4): Collection
    {
        if (!$product) {
            return Product::where('status', 'published')
                ->latest('id')
                ->limit($limit)
                ->get();
        }

        return Product::where('status', 'published')
            ->where('id', '!=', $product->id)
            ->where(function ($q) use ($product) {
                if ($product->category_id) {
                    $q->where('category_id', $product->category_id);
                }
                if ($product->brand_id) {
                    $q->orWhere('brand_id', $product->brand_id);
                }
            })
            ->with(['category', 'brand'])
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
