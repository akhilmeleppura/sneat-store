<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Cart\Models\AbandonedCartRecovery;
use Modules\Cart\Models\Cart;
use Modules\Context\Models\Tenant;

class AbandonedCartDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        $store = \Modules\Context\Models\Store::first();
        if (!$tenant || !$store) {
            return;
        }
        $tenantId = $tenant->id;
        $storeId = $store->id;

        $cart = Cart::first();
        if (!$cart) {
            $cart = Cart::create([
                'tenant_id'  => $tenantId,
                'store_id'   => $storeId,
                'cart_token' => Str::random(32),
                'currency'   => 'USD',
            ]);
        }
        $cartId = $cart->id;

        AbandonedCartRecovery::firstOrCreate(
            ['customer_email' => 'sarah.connor@gmail.com'],
            [
                'tenant_id'              => $tenantId,
                'cart_id'                => $cartId,
                'customer_phone'         => '+1 (555) 482-9102',
                'cart_subtotal'          => 249.99,
                'currency'               => 'USD',
                'recovery_token'         => Str::random(40),
                'status'                 => 'first_reminder_sent',
                'recovery_discount_code' => 'COMEBACK10',
                'items_count'            => 3,
                'sent_at'                => now()->subHours(4),
            ]
        );

        AbandonedCartRecovery::firstOrCreate(
            ['customer_email' => 'david.beck@outlook.com'],
            [
                'tenant_id'              => $tenantId,
                'cart_id'                => $cartId,
                'customer_phone'         => '+1 (555) 918-2031',
                'cart_subtotal'          => 599.00,
                'currency'               => 'USD',
                'recovery_token'         => Str::random(40),
                'status'                 => 'recovered',
                'recovery_discount_code' => 'COMEBACK10',
                'items_count'            => 2,
                'sent_at'                => now()->subDays(1),
                'recovered_at'           => now()->subHours(6),
            ]
        );
    }
}
