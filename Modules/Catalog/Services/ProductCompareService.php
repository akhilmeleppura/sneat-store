<?php

namespace Modules\Catalog\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Modules\Catalog\Models\Product;

class ProductCompareService
{
    protected const COMPARE_KEY = 'store_compare_list';
    public const MAX_COMPARE_ITEMS = 4;

    /**
     * Get compared products collection.
     */
    public function getComparedProducts(): Collection
    {
        $ids = Session::get(self::COMPARE_KEY, []);
        if (empty($ids)) {
            return collect();
        }

        return Product::whereIn('id', $ids)
            ->where('status', 'published')
            ->with(['category', 'brand', 'variants', 'reviews'])
            ->get();
    }

    /**
     * Add product to compare list.
     */
    public function add(int $productId): array
    {
        $list = Session::get(self::COMPARE_KEY, []);
        if (in_array($productId, $list)) {
            return ['status' => 'info', 'message' => 'Product is already in your comparison list.', 'count' => count($list)];
        }

        if (count($list) >= self::MAX_COMPARE_ITEMS) {
            return ['status' => 'warning', 'message' => 'You can compare up to ' . self::MAX_COMPARE_ITEMS . ' products at a time.', 'count' => count($list)];
        }

        $list[] = $productId;
        Session::put(self::COMPARE_KEY, $list);

        return ['status' => 'success', 'message' => 'Product added to comparison list.', 'count' => count($list)];
    }

    /**
     * Remove product from compare list.
     */
    public function remove(int $productId): array
    {
        $list = Session::get(self::COMPARE_KEY, []);
        $list = array_values(array_diff($list, [$productId]));
        Session::put(self::COMPARE_KEY, $list);

        return ['status' => 'success', 'message' => 'Product removed from comparison list.', 'count' => count($list)];
    }

    /**
     * Clear comparison list.
     */
    public function clear(): void
    {
        Session::forget(self::COMPARE_KEY);
    }
}
