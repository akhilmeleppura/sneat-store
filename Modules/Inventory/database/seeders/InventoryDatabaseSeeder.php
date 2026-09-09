<?php

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Models\InventoryStock;
use Modules\Inventory\Models\InventoryTransaction;

class InventoryDatabaseSeeder extends Seeder
{
    /**
     * Run the Inventory module database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'sneat-global'],
            ['name' => 'Sneat Global Retail']
        );

        $branches = Branch::where('tenant_id', $tenant->id)->get();
        if ($branches->isEmpty()) {
            $this->command->warn('No branches found for tenant. Please run ContextDatabaseSeeder first.');
            return;
        }

        $variants = ProductVariant::where('tenant_id', $tenant->id)->with('product')->get();
        if ($variants->isEmpty()) {
            $this->command->warn('No product variants found. Please run CatalogDatabaseSeeder first.');
            return;
        }

        foreach ($branches as $branch) {
            foreach ($variants as $variant) {
                // Determine initial stock quantity based on branch
                $initialQty = $branch->is_default ? 35 : 15;
                $reorderLevel = 5;

                $stock = InventoryStock::firstOrCreate(
                    [
                        'tenant_id'          => $tenant->id,
                        'tenant_branch_id'   => $branch->id,
                        'product_variant_id' => $variant->id,
                    ],
                    [
                        'product_id'        => $variant->product_id,
                        'quantity_on_hand'  => $initialQty,
                        'quantity_reserved' => 0,
                        'reorder_level'     => $reorderLevel,
                    ]
                );

                // Ensure initial transaction ledger entry exists
                InventoryTransaction::firstOrCreate(
                    [
                        'tenant_id'          => $tenant->id,
                        'tenant_branch_id'   => $branch->id,
                        'product_variant_id' => $variant->id,
                        'type'               => 'init',
                    ],
                    [
                        'quantity'       => $stock->quantity_on_hand,
                        'balance_after'  => $stock->quantity_on_hand,
                        'reference_type' => 'initial_intake',
                        'reference_id'   => null,
                        'note'           => "Initial stock intake for {$variant->sku} at {$branch->name}",
                    ]
                );
            }
        }

        $this->command->info('Inventory: Stocks and ledger transactions initialized for all branches.');
    }
}
