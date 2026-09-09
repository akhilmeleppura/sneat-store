<?php

namespace Modules\Inventory\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Inventory\Models\InventoryStock;
use Modules\Inventory\Models\InventoryTransaction;

class InventoryService
{
    /**
     * Get available stock quantity for a variant at a specific branch or across all branches.
     */
    public function getAvailableStock(int $variantId, ?int $branchId = null): int
    {
        $query = InventoryStock::where('product_variant_id', $variantId);

        if ($branchId) {
            $query->where('tenant_branch_id', $branchId);
        }

        $stocks = $query->get();

        return (int) $stocks->sum('quantity_on_hand') - (int) $stocks->sum('quantity_reserved');
    }

    /**
     * Atomically adjust stock with pessimistic locking and write an immutable audit transaction.
     *
     * @throws Exception
     */
    public function adjustStock(
        int $variantId,
        int $branchId,
        int $delta,
        string $type = 'adjustment',
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $note = null
    ): InventoryStock {
        return DB::transaction(function () use ($variantId, $branchId, $delta, $type, $referenceType, $referenceId, $note) {
            $variant = ProductVariant::findOrFail($variantId);

            // Fetch or create stock record with row locking
            $stock = InventoryStock::where('product_variant_id', $variantId)
                ->where('tenant_branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = new InventoryStock([
                    'tenant_id'          => $variant->tenant_id,
                    'tenant_branch_id'   => $branchId,
                    'product_id'         => $variant->product_id,
                    'product_variant_id' => $variantId,
                    'quantity_on_hand'   => 0,
                    'quantity_reserved'  => 0,
                    'reorder_level'      => config('inventory.default_reorder_level', 5),
                ]);
            }

            $newOnHand = $stock->quantity_on_hand + $delta;
            if ($newOnHand < 0 && ! config('inventory.allow_backorders', false)) {
                throw new Exception("Insufficient stock: Cannot reduce {$stock->quantity_on_hand} by " . abs($delta));
            }

            $stock->quantity_on_hand = $newOnHand;
            $stock->save();

            // Record immutable audit transaction
            InventoryTransaction::create([
                'tenant_id'          => $stock->tenant_id,
                'tenant_branch_id'   => $branchId,
                'product_variant_id' => $variantId,
                'user_id'            => Auth::id(),
                'type'               => $type,
                'quantity'           => $delta,
                'balance_after'      => $stock->quantity_on_hand,
                'reference_type'     => $referenceType,
                'reference_id'       => $referenceId,
                'note'               => $note,
            ]);

            $availableStock = $stock->quantity_on_hand - $stock->quantity_reserved;
            $reorderLevel = (int) ($stock->reorder_level ?? 5);
            if ($availableStock <= $reorderLevel) {
                try {
                    event(new \Modules\Inventory\Events\LowStockAlertEvent($variant, $branchId, $availableStock));
                } catch (\Throwable $e) {
                    // Silently catch
                }
            }

            return $stock;
        });
    }

    /**
     * Atomically reserve stock for an order/checkout session.
     *
     * @throws Exception
     */
    public function reserveStock(int $variantId, int $branchId, int $quantity, ?string $orderRef = null): bool
    {
        if ($quantity <= 0) {
            return false;
        }

        return DB::transaction(function () use ($variantId, $branchId, $quantity, $orderRef) {
            $stock = InventoryStock::where('product_variant_id', $variantId)
                ->where('tenant_branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                return false;
            }

            $available = $stock->quantity_on_hand - $stock->quantity_reserved;
            if ($available < $quantity) {
                return false;
            }

            $stock->quantity_reserved += $quantity;
            $stock->save();

            InventoryTransaction::create([
                'tenant_id'          => $stock->tenant_id,
                'tenant_branch_id'   => $branchId,
                'product_variant_id' => $variantId,
                'user_id'            => Auth::id(),
                'type'               => 'order_reserved',
                'quantity'           => $quantity,
                'balance_after'      => $stock->quantity_on_hand,
                'reference_type'     => 'order',
                'reference_id'       => $orderRef,
                'note'               => "Reserved {$quantity} units for Order {$orderRef}",
            ]);

            return true;
        });
    }

    /**
     * Atomically release previously reserved stock (e.g. cancelled order or expired cart).
     */
    public function releaseReservedStock(int $variantId, int $branchId, int $quantity, ?string $orderRef = null): bool
    {
        return DB::transaction(function () use ($variantId, $branchId, $quantity, $orderRef) {
            $stock = InventoryStock::where('product_variant_id', $variantId)
                ->where('tenant_branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                return false;
            }

            $stock->quantity_reserved = max(0, $stock->quantity_reserved - $quantity);
            $stock->save();

            InventoryTransaction::create([
                'tenant_id'          => $stock->tenant_id,
                'tenant_branch_id'   => $branchId,
                'product_variant_id' => $variantId,
                'user_id'            => Auth::id(),
                'type'               => 'order_released',
                'quantity'           => -$quantity,
                'balance_after'      => $stock->quantity_on_hand,
                'reference_type'     => 'order',
                'reference_id'       => $orderRef,
                'note'               => "Released {$quantity} reserved units for Order {$orderRef}",
            ]);

            return true;
        });
    }

    /**
     * Commit and deduct reserved stock when an order is finalized and paid.
     */
    public function commitReservedStock(int $variantId, int $branchId, int $quantity, ?string $orderRef = null): bool
    {
        return DB::transaction(function () use ($variantId, $branchId, $quantity, $orderRef) {
            $stock = InventoryStock::where('product_variant_id', $variantId)
                ->where('tenant_branch_id', $branchId)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                return false;
            }

            $stock->quantity_on_hand = max(0, $stock->quantity_on_hand - $quantity);
            $stock->quantity_reserved = max(0, $stock->quantity_reserved - $quantity);
            $stock->save();

            InventoryTransaction::create([
                'tenant_id'          => $stock->tenant_id,
                'tenant_branch_id'   => $branchId,
                'product_variant_id' => $variantId,
                'user_id'            => Auth::id(),
                'type'               => 'order_fulfilled',
                'quantity'           => -$quantity,
                'balance_after'      => $stock->quantity_on_hand,
                'reference_type'     => 'order',
                'reference_id'       => $orderRef,
                'note'               => "Fulfilled Order {$orderRef}: Deducted {$quantity} units",
            ]);

            $availableStock = $stock->quantity_on_hand - $stock->quantity_reserved;
            $reorderLevel = (int) ($stock->reorder_level ?? 5);
            if ($availableStock <= $reorderLevel) {
                try {
                    $variant = ProductVariant::find($variantId);
                    if ($variant) {
                        event(new \Modules\Inventory\Events\LowStockAlertEvent($variant, $branchId, $availableStock));
                    }
                } catch (\Throwable $e) {
                    // Silently catch
                }
            }

            return true;
        });
    }
}
