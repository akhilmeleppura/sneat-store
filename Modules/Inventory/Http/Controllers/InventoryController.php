<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Models\Branch;
use Modules\Inventory\Models\InventoryStock;
use Modules\Inventory\Models\InventoryTransaction;
use Modules\Inventory\Services\InventoryService;

class InventoryController extends Controller
{
    /**
     * Display inventory stock levels.
     */
    public function index(Request $request)
    {
        $query = InventoryStock::with(['product', 'variant.attributeValues', 'branch']);

        if ($request->filled('branch_id')) {
            $query->where('tenant_branch_id', $request->branch_id);
        }

        if ($request->filled('filter') && $request->filter === 'low_stock') {
            $query->lowStock();
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('variant', function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%");
            });
        }

        $stocks = $query->paginate(20);
        $branches = Branch::active()->get();
        $variants = ProductVariant::with('product')->get();

        return view('inventory::index', compact('stocks', 'branches', 'variants'));
    }

    /**
     * Atomically adjust stock level for a product variant at a branch.
     */
    public function adjust(Request $request, InventoryService $inventoryService)
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'tenant_branch_id'   => 'required|exists:tenant_branches,id',
            'delta'              => 'required|integer|not_in:0',
            'type'               => 'required|string|in:stock_in,stock_out,adjustment_positive,adjustment_negative,initial',
            'note'               => 'nullable|string|max:255',
        ]);

        try {
            $inventoryService->adjustStock(
                (int) $validated['product_variant_id'],
                (int) $validated['tenant_branch_id'],
                (int) $validated['delta'],
                $validated['type'],
                'manual_adjustment',
                null,
                $validated['note'] ?? 'Manual stock adjustment via dashboard'
            );

            return redirect()->route('inventory.index')
                ->with('success', 'Stock level adjusted successfully.');
        } catch (Exception $e) {
            return redirect()->route('inventory.index')
                ->with('error', 'Failed to adjust stock: ' . $e->getMessage());
        }
    }

    /**
     * Display immutable audit trail transaction logs.
     */
    public function transactions(Request $request)
    {
        $query = InventoryTransaction::with(['variant.product', 'branch', 'user'])->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('branch_id')) {
            $query->where('tenant_branch_id', $request->branch_id);
        }

        $transactions = $query->paginate(25);
        $branches = Branch::active()->get();

        return view('inventory::transactions', compact('transactions', 'branches'));
    }
}
