<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Catalog\Database\Seeders\CatalogDatabaseSeeder;
use Modules\Context\Database\Seeders\ContextDatabaseSeeder;
use Modules\Inventory\Database\Seeders\InventoryDatabaseSeeder;
use Modules\Marketplace\Database\Seeders\MarketplaceDatabaseSeeder;
use Modules\Order\Database\Seeders\OrderDatabaseSeeder;
use Modules\Payment\Database\Seeders\PaymentDatabaseSeeder;
use Modules\Permission\Database\Seeders\PermissionDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Roles & Permissions
        if (class_exists(PermissionDatabaseSeeder::class)) {
            $this->call([PermissionDatabaseSeeder::class]);
        }

        // 2. Multi-Tenant Context (Tenants, Stores, Branches, Currencies)
        if (class_exists(ContextDatabaseSeeder::class)) {
            $this->call([ContextDatabaseSeeder::class]);
        }

        // 3. Marketplace (Vendors, Vendor Users, Payouts)
        if (class_exists(MarketplaceDatabaseSeeder::class)) {
            $this->call([MarketplaceDatabaseSeeder::class]);
        }

        // 4. Catalog (Categories, Brands, Attributes, Products, Variants, Reviews)
        if (class_exists(CatalogDatabaseSeeder::class)) {
            $this->call([CatalogDatabaseSeeder::class]);
        }

        // 5. Inventory (Branch Stocks & Ledger Entries)
        if (class_exists(InventoryDatabaseSeeder::class)) {
            $this->call([InventoryDatabaseSeeder::class]);
        }

        // 6. Orders (Shipping Methods, Coupons, Orders, Shipments)
        if (class_exists(OrderDatabaseSeeder::class)) {
            $this->call([OrderDatabaseSeeder::class]);
        }

        // 7. Payments (Payment Options, Gateway Configurations, Audit Transactions)
        if (class_exists(PaymentDatabaseSeeder::class)) {
            $this->call([PaymentDatabaseSeeder::class]);
        }

        // 8. Legacy / Auxiliary Seeders
        if (class_exists(PaymentOptionSeeder::class)) {
            $this->call([PaymentOptionSeeder::class]);
        }

        if (class_exists(EcommerceStoreSeeder::class)) {
            $this->call([EcommerceStoreSeeder::class]);
        }

        if (class_exists(AbandonedCartDemoSeeder::class)) {
            $this->call([AbandonedCartDemoSeeder::class]);
        }

        if (class_exists(AffiliateDemoSeeder::class)) {
            $this->call([AffiliateDemoSeeder::class]);
        }

        // 9. Default Administrator Accounts
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name'             => 'Admin',
                'password'         => Hash::make('Admin@123'),
                'is_supreme_admin' => 1,
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@sneat.test'],
            [
                'name'             => 'Supreme Admin',
                'password'         => Hash::make('Admin@123'),
                'is_supreme_admin' => 1,
            ]
        );
    }
}
