<?php

namespace Modules\Payment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Context\Models\Tenant;
use Modules\Order\Models\Order;
use Modules\Payment\Models\PaymentTransaction;

class PaymentDatabaseSeeder extends Seeder
{
    /**
     * Run Payment module database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'sneat-global'],
            ['name' => 'Sneat Global Retail']
        );

        // 1. Seed Payment Options Table if exists
        if (Schema::hasTable('payment_options')) {
            $paymentOptions = [
                [
                    'name'        => 'Credit / Debit Card (Stripe)',
                    'slug'        => 'credit-card',
                    'description' => 'Secure card checkout powered by Stripe.',
                    'gateway'     => 'stripe',
                    'is_active'   => true,
                ],
                [
                    'name'        => 'PayPal Express',
                    'slug'        => 'paypal',
                    'description' => 'Fast checkout using your PayPal balance or linked cards.',
                    'gateway'     => 'paypal',
                    'is_active'   => true,
                ],
                [
                    'name'        => 'Direct Bank Transfer',
                    'slug'        => 'bank-transfer',
                    'description' => 'Electronic wire transfer to central corporate account.',
                    'gateway'     => 'offline',
                    'is_active'   => true,
                ],
                [
                    'name'        => 'Cash On Delivery (COD)',
                    'slug'        => 'cash-on-delivery',
                    'description' => 'Pay cash upon delivery verification.',
                    'gateway'     => 'offline',
                    'is_active'   => true,
                ],
                [
                    'name'        => 'Apple Pay / Google Pay',
                    'slug'        => 'digital-wallets',
                    'description' => 'One-tap biometric wallet checkout.',
                    'gateway'     => 'stripe',
                    'is_active'   => true,
                ],
            ];

            foreach ($paymentOptions as $option) {
                DB::table('payment_options')->updateOrInsert(
                    ['slug' => $option['slug']],
                    $option
                );
            }
        }

        // 2. Seed Payment Transactions linked to existing Orders
        $orders = Order::where('tenant_id', $tenant->id)->take(3)->get();

        foreach ($orders as $index => $order) {
            $gateway = $order->payment_method ?: 'stripe';
            $ref = 'TXN-' . date('Ymd') . '-' . str_pad((string)($order->id * 100 + $index), 6, '0', STR_PAD_LEFT);

            PaymentTransaction::updateOrCreate(
                ['transaction_reference' => $ref],
                [
                    'tenant_id'              => $tenant->id,
                    'order_id'               => $order->id,
                    'gateway'                => $gateway,
                    'amount'                 => $order->grand_total,
                    'currency'               => $order->currency ?: 'USD',
                    'status'                 => 'successful',
                    'payment_method_details' => [
                        'type'        => $gateway,
                        'card_brand'  => 'Visa',
                        'last4'       => '4242',
                        'exp_month'   => 12,
                        'exp_year'    => 2028,
                    ],
                    'payload'                => [
                        'auth_code' => 'AUTH_' . md5($ref),
                        'risk_score' => 15,
                    ],
                ]
            );
        }

        $this->command->info('Payment: Payment options and audit transactions seeded successfully.');
    }
}
