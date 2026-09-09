<?php

namespace Modules\Context\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Currency;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;

class ContextDatabaseSeeder extends Seeder
{
    /**
     * Run the Context module database seeds.
     */
    public function run(): void
    {
        // 1. Primary Tenant
        $tenant = Tenant::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'sneat-global'],
            [
                'name'   => 'Sneat Global Retail',
                'domain' => 'sneat.test',
            ]
        );

        // 2. Primary Store
        $store = Store::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'sneat-flagship'],
            [
                'name'       => 'Sneat Flagship Store',
                'is_default' => true,
            ]
        );

        // 3. Branches / Warehouses
        $branchMain = Branch::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'central-warehouse'],
            [
                'store_id'   => $store->id,
                'name'       => 'Central Fulfillment Center',
                'code'       => 'BR-001',
                'is_default' => true,
            ]
        );

        $branchWest = Branch::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'west-coast-hub'],
            [
                'store_id'   => $store->id,
                'name'       => 'West Coast Logistics Hub',
                'code'       => 'BR-002',
                'is_default' => false,
            ]
        );

        // Set context
        Context::setTenant($tenant);
        Context::setStore($store);
        Context::setBranch($branchMain);

        // 4. Global International Currencies
        $currencies = [
            [
                'code'            => 'USD',
                'name'            => 'US Dollar',
                'symbol'          => '$',
                'exchange_rate'   => 1.0000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => true,
                'is_active'       => true,
            ],
            [
                'code'            => 'EUR',
                'name'            => 'Euro',
                'symbol'          => '€',
                'exchange_rate'   => 0.9200,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'GBP',
                'name'            => 'British Pound',
                'symbol'          => '£',
                'exchange_rate'   => 0.7800,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'CAD',
                'name'            => 'Canadian Dollar',
                'symbol'          => 'CA$',
                'exchange_rate'   => 1.3500,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'AUD',
                'name'            => 'Australian Dollar',
                'symbol'          => 'A$',
                'exchange_rate'   => 1.5200,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'INR',
                'name'            => 'Indian Rupee',
                'symbol'          => '₹',
                'exchange_rate'   => 83.5000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'JPY',
                'name'            => 'Japanese Yen',
                'symbol'          => '¥',
                'exchange_rate'   => 155.0000,
                'decimal_places'  => 0,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
        ];

        foreach ($currencies as $curr) {
            Currency::withoutGlobalScopes()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $curr['code']],
                $curr
            );
        }

        // 5. Secondary Demo Tenant (to verify multi-tenant isolation out-of-the-box)
        $demoTenant = Tenant::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'demotenant'],
            ['name' => 'Demo Tenant Enterprises']
        );

        $demoStore = Store::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $demoTenant->id, 'slug' => 'main-store'],
            ['name' => 'Demo Main Store', 'is_default' => true]
        );

        Branch::withoutGlobalScopes()->firstOrCreate(
            ['tenant_id' => $demoTenant->id, 'slug' => 'main-branch'],
            [
                'store_id'   => $demoStore->id,
                'name'       => 'Demo Main Branch',
                'code'       => 'DEMO-001',
                'is_default' => true,
            ]
        );

        Currency::withoutGlobalScopes()->updateOrCreate(
            ['tenant_id' => $demoTenant->id, 'code' => 'USD'],
            [
                'name'            => 'US Dollar',
                'symbol'          => '$',
                'exchange_rate'   => 1.0000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => true,
                'is_active'       => true,
            ]
        );

        $this->command->info('Context: Tenants, Stores, Branches, and Currencies seeded successfully.');
    }
}
