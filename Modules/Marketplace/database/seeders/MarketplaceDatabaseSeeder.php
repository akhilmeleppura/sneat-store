<?php

namespace Modules\Marketplace\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Models\VendorPayout;
use Spatie\Permission\Models\Role;

class MarketplaceDatabaseSeeder extends Seeder
{
    /**
     * Run Marketplace module database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'sneat-global'],
            ['name' => 'Sneat Global Retail']
        );

        $store = Store::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'sneat-flagship'],
            ['name' => 'Sneat Flagship Store', 'is_default' => true]
        );

        $branch = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'central-warehouse'],
            ['store_id' => $store->id, 'name' => 'Central Fulfillment Center', 'code' => 'BR-001', 'is_default' => true]
        );

        // 1. Dedicated Vendor User Account
        $vendorUser1 = User::updateOrCreate(
            ['email' => 'vendor@sneat.test'],
            [
                'name'              => 'Apex Vendor Partner',
                'password'          => Hash::make('password'),
                'is_supreme_admin'  => false,
                'tenant_id'         => $tenant->id,
                'store_id'          => $store->id,
                'tenant_branch_id'  => $branch->id,
                'email_verified_at' => now(),
            ]
        );

        $vendorUser2 = User::updateOrCreate(
            ['email' => 'urban@sneat.test'],
            [
                'name'              => 'Urban Lifestyle Goods Rep',
                'password'          => Hash::make('password'),
                'is_supreme_admin'  => false,
                'tenant_id'         => $tenant->id,
                'store_id'          => $store->id,
                'tenant_branch_id'  => $branch->id,
                'email_verified_at' => now(),
            ]
        );

        // Assign 'Vendor' role if role exists
        if (Role::where('name', 'Vendor')->where('guard_name', 'web')->exists()) {
            $vendorUser1->assignRole('Vendor');
            $vendorUser2->assignRole('Vendor');
        }

        // 2. Marketplace Vendor Profiles
        $vendor1 = Vendor::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'apex-tech'],
            [
                'user_id'         => $vendorUser1->id,
                'name'            => 'Apex Tech Solutions',
                'email'           => 'apex@marketplace.test',
                'phone'           => '+1 555-019-2834',
                'description'     => 'Authorized distributor of high-performance computing and audio electronics.',
                'commission_rate' => 12.50,
                'balance'         => 1850.00,
                'status'          => 'active',
                'payout_info'     => [
                    'bank_name'      => 'JPMorgan Chase',
                    'account_number' => '****4829',
                    'routing_number' => '021000021',
                ],
            ]
        );

        $vendor2 = Vendor::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'urban-lifestyle'],
            [
                'user_id'         => $vendorUser2->id,
                'name'            => 'Urban Lifestyle Goods',
                'email'           => 'urban@marketplace.test',
                'phone'           => '+1 555-014-9922',
                'description'     => 'Contemporary ergonomic workspace furniture, premium footwear, and everyday essentials.',
                'commission_rate' => 10.00,
                'balance'         => 720.00,
                'status'          => 'active',
                'payout_info'     => [
                    'bank_name'      => 'Wells Fargo',
                    'account_number' => '****1192',
                    'routing_number' => '121000247',
                ],
            ]
        );

        // 3. Sample Payout Requests
        VendorPayout::updateOrCreate(
            ['tenant_id' => $tenant->id, 'vendor_id' => $vendor1->id, 'transaction_reference' => 'PAY-APEX-001'],
            [
                'amount'                => 500.00,
                'currency'              => 'USD',
                'status'                => 'completed',
                'payout_method'         => 'bank_transfer',
                'transaction_reference' => 'PAY-APEX-001',
                'notes'                 => 'Monthly disbursement for August settlement.',
                'processed_at'          => now()->subDays(5),
            ]
        );

        VendorPayout::updateOrCreate(
            ['tenant_id' => $tenant->id, 'vendor_id' => $vendor1->id, 'transaction_reference' => 'PAY-APEX-002'],
            [
                'amount'                => 350.00,
                'currency'              => 'USD',
                'status'                => 'requested',
                'payout_method'         => 'bank_transfer',
                'transaction_reference' => 'PAY-APEX-002',
                'notes'                 => 'Pending approval for early September sales.',
                'processed_at'          => null,
            ]
        );

        $this->command->info('Marketplace: Vendors, User Accounts, and Payout records seeded successfully.');
    }
}
