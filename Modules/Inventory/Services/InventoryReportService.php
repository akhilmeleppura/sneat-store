<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\ProductVariant;
use Modules\Inventory\Models\InventoryStock;

class InventoryReportService
{
    /**
     * Get aggregate inventory health & valuation metrics.
     */
    public function getInventoryOverview(?int $branchId = null): array
    {
        $query = InventoryStock::query();

        if ($branchId) {
            $query->where('tenant_branch_id', $branchId);
        }

        $totalRecords = (clone $query)->count();
        $totalOnHand = (int) (clone $query)->sum('quantity_on_hand');
        $totalReserved = (int) (clone $query)->sum('quantity_reserved');
        $totalAvailable = max(0, $totalOnHand - $totalReserved);

        // Calculate out of stock and low stock counts
        $outOfStockCount = (clone $query)
            ->whereRaw('(quantity_on_hand - quantity_reserved) <= 0')
            ->count();

        $lowStockCount = (clone $query)
            ->whereRaw('(quantity_on_hand - quantity_reserved) > 0')
            ->whereRaw('(quantity_on_hand - quantity_reserved) <= reorder_level')
            ->count();

        // Estimated Inventory Valuation based on variant prices
        $valuation = (float) InventoryStock::join('product_variants', 'inventory_stocks.product_variant_id', '=', 'product_variants.id')
            ->when($branchId, fn ($q) => $q->where('inventory_stocks.tenant_branch_id', $branchId))
            ->sum(DB::raw('inventory_stocks.quantity_on_hand * COALESCE(product_variants.cost_price, product_variants.price, 0)'));

        return [
            'total_sku_records'    => $totalRecords,
            'total_on_hand'        => $totalOnHand,
            'total_reserved'       => $totalReserved,
            'total_available'      => $totalAvailable,
            'out_of_stock_count'   => $outOfStockCount,
            'low_stock_count'      => $lowStockCount,
            'estimated_valuation'  => round($valuation, 2),
        ];
    }

    /**
     * Get list of critical low stock items.
     */
    public function getLowStockItems(int $limit = 20, ?int $branchId = null): array
    {
        $query = InventoryStock::with(['variant.product', 'branch'])
            ->whereRaw('(quantity_on_hand - quantity_reserved) <= reorder_level');

        if ($branchId) {
            $query->where('tenant_branch_id', $branchId);
        }

        return $query->orderByRaw('(quantity_on_hand - quantity_reserved) ASC')
            ->take($limit)
            ->get()
            ->map(function ($stock) {
                $available = (int) ($stock->quantity_on_hand - $stock->quantity_reserved);
                return [
                    'stock_id'        => $stock->id,
                    'variant_id'      => $stock->product_variant_id,
                    'sku'             => $stock->variant?->sku ?? 'N/A',
                    'product_name'    => $stock->variant?->product?->name ?? 'Unknown',
                    'branch_name'     => $stock->branch?->name ?? "Branch #{$stock->tenant_branch_id}",
                    'on_hand'         => (int) $stock->quantity_on_hand,
                    'reserved'        => (int) $stock->quantity_reserved,
                    'available'       => $available,
                    'reorder_level'   => (int) $stock->reorder_level,
                    'is_out_of_stock' => $available <= 0,
                ];
            })
            ->toArray();
    }
}
