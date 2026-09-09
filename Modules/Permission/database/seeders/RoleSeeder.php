<?php

namespace Modules\Permission\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Supreme Admin
        $supremeAdmin = Role::firstOrCreate(
            ['name' => 'Supreme Admin', 'guard_name' => 'web'],
            ['status' => 1]
        );
        $allPermissions = Permission::all();
        $supremeAdmin->syncPermissions($allPermissions);

        // 2. Tenant Admin
        $tenantAdmin = Role::firstOrCreate(
            ['name' => 'Tenant Admin', 'guard_name' => 'web'],
            ['status' => 1]
        );
        $tenantAdminPermissions = Permission::whereNotIn('name', [
            'tenant.create',
            'tenant.delete',
        ])->get();
        $tenantAdmin->syncPermissions($tenantAdminPermissions);

        // 3. Store Manager
        $storeManager = Role::firstOrCreate(
            ['name' => 'Store Manager', 'guard_name' => 'web'],
            ['status' => 1]
        );
        $storeManagerPermissions = Permission::where(function ($query) {
            $query->where('name', 'like', 'catalog.%')
                  ->orWhere('name', 'like', 'inventory.%')
                  ->orWhere('name', 'like', 'order.%')
                  ->orWhere('name', 'like', 'shipment.%')
                  ->orWhere('name', 'like', 'shipping-method.%')
                  ->orWhere('name', 'like', 'coupon.%')
                  ->orWhere('name', 'like', 'gift-card.%')
                  ->orWhere('name', 'like', 'tax-rate.%')
                  ->orWhere('name', 'like', 'newsletter.%')
                  ->orWhere('name', 'like', 'loyalty.%')
                  ->orWhere('name', 'like', 'notification.%');
        })->get();
        $storeManager->syncPermissions($storeManagerPermissions);

        // 4. Vendor Partner
        $vendorRole = Role::firstOrCreate(
            ['name' => 'Vendor', 'guard_name' => 'web'],
            ['status' => 1]
        );
        $vendorPermissions = Permission::whereIn('name', [
            'catalog.product.view',
            'catalog.product.create',
            'catalog.product.edit',
            'order.view',
            'marketplace.vendor.view',
            'marketplace.payout.view',
            'notification.view',
        ])->get();
        $vendorRole->syncPermissions($vendorPermissions);

        // 5. Customer
        $customerRole = Role::firstOrCreate(
            ['name' => 'Customer', 'guard_name' => 'web'],
            ['status' => 1]
        );
        $customerPermissions = Permission::whereIn('name', [
            'catalog.product.view',
            'notification.view',
        ])->get();
        $customerRole->syncPermissions($customerPermissions);

        $this->command->info('Standard Roles created and permissions assigned successfully.');
    }
}
