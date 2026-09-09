<?php

namespace Modules\Order\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Order\Models\Coupon;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Models\Shipment;
use Modules\Order\Models\ShippingMethod;

class OrderDatabaseSeeder extends Seeder
{
    /**
     * Run the Order module database seeds.
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

        // 1. Shipping Methods
        $methods = [
            [
                'name'                    => 'FedEx Priority Overnight',
                'code'                    => 'FEDEX_OVERNIGHT',
                'carrier'                 => 'FedEx',
                'rate_type'               => 'flat',
                'base_rate'               => 35.00,
                'free_shipping_threshold' => null,
                'min_days'                => 1,
                'max_days'                => 1,
                'description'             => 'Guaranteed next business day delivery by 10:30 AM to most areas.',
                'sort_order'              => 1,
                'is_active'               => true,
            ],
            [
                'name'                    => 'UPS Ground Standard',
                'code'                    => 'UPS_GROUND',
                'carrier'                 => 'UPS',
                'rate_type'               => 'flat',
                'base_rate'               => 12.00,
                'free_shipping_threshold' => 150.00,
                'min_days'                => 3,
                'max_days'                => 5,
                'description'             => 'Economical ground delivery with day-definite tracking.',
                'sort_order'              => 2,
                'is_active'               => true,
            ],
            [
                'name'                    => 'DHL Express International',
                'code'                    => 'DHL_EXPRESS',
                'carrier'                 => 'DHL',
                'rate_type'               => 'flat',
                'base_rate'               => 24.00,
                'free_shipping_threshold' => null,
                'min_days'                => 2,
                'max_days'                => 3,
                'description'             => 'Rapid door-to-door express parcel delivery service.',
                'sort_order'              => 3,
                'is_active'               => true,
            ],
            [
                'name'                    => 'Free Standard Shipping',
                'code'                    => 'FREE_GROUND',
                'carrier'                 => 'Standard Post',
                'rate_type'               => 'free',
                'base_rate'               => 0.00,
                'free_shipping_threshold' => 100.00,
                'min_days'                => 5,
                'max_days'                => 8,
                'description'             => 'Complimentary doorstep ground delivery on orders over $100.',
                'sort_order'              => 4,
                'is_active'               => true,
            ],
        ];

        $createdMethods = [];
        foreach ($methods as $m) {
            $createdMethods[$m['code']] = ShippingMethod::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $m['code']],
                array_merge($m, ['tenant_id' => $tenant->id])
            );
        }

        // 2. Promotional Coupons
        $coupons = [
            [
                'code'                 => 'WELCOME10',
                'name'                 => 'New Customer 10% Discount',
                'description'          => 'Get 10% off your entire cart on orders of $50 or more.',
                'type'                 => 'percentage',
                'value'                => 10.00,
                'min_order_amount'     => 50.00,
                'max_discount_amount'  => 100.00,
                'usage_limit'          => 1000,
                'usage_limit_per_user' => 1,
                'is_active'            => true,
            ],
            [
                'code'                 => 'SUMMER25',
                'name'                 => 'Summer Savings $25 Off',
                'description'          => 'Take $25 off orders above $150.',
                'type'                 => 'fixed',
                'value'                => 25.00,
                'min_order_amount'     => 150.00,
                'max_discount_amount'  => null,
                'usage_limit'          => 500,
                'usage_limit_per_user' => 1,
                'is_active'            => true,
            ],
            [
                'code'                 => 'FREESHIP',
                'name'                 => 'Free Standard Shipping Promo',
                'description'          => 'Enjoy instant free shipping on your purchase.',
                'type'                 => 'fixed',
                'value'                => 15.00,
                'min_order_amount'     => 75.00,
                'max_discount_amount'  => null,
                'usage_limit'          => 2000,
                'usage_limit_per_user' => 2,
                'is_active'            => true,
            ],
            [
                'code'                 => 'VIP20',
                'name'                 => 'VIP Member 20% Discount',
                'description'          => 'Exclusive 20% discount for registered VIP shoppers.',
                'type'                 => 'percentage',
                'value'                => 20.00,
                'min_order_amount'     => 200.00,
                'max_discount_amount'  => 250.00,
                'usage_limit'          => 300,
                'usage_limit_per_user' => 1,
                'is_active'            => true,
            ],
        ];

        foreach ($coupons as $c) {
            Coupon::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $c['code']],
                array_merge($c, ['tenant_id' => $tenant->id])
            );
        }

        // 3. Customer Accounts
        $customer1 = User::where('email', 'john.doe@example.com')->first();
        $customer2 = User::where('email', 'jane.smith@example.com')->first();

        // 4. Sample Order 1: Dispatched Shipment
        $firstVariant = ProductVariant::where('tenant_id', $tenant->id)->with('product')->first();
        $selectedShipMethod = $createdMethods['FEDEX_OVERNIGHT'] ?? ShippingMethod::first();

        if ($customer1 && $firstVariant && $selectedShipMethod) {
            $order1 = Order::updateOrCreate(
                ['order_number' => 'ORD-' . date('Ymd') . '-00101'],
                [
                    'tenant_id'               => $tenant->id,
                    'store_id'                => $store->id,
                    'tenant_branch_id'        => $branch->id,
                    'user_id'                 => $customer1->id,
                    'customer_name'           => $customer1->name,
                    'customer_email'          => $customer1->email,
                    'customer_phone'          => '+1 555-234-5678',
                    'shipping_address'        => [
                        'street'      => '742 Evergreen Terrace',
                        'city'        => 'Springfield',
                        'state'       => 'OR',
                        'postal_code' => '97477',
                        'country'     => 'United States',
                    ],
                    'subtotal'                => $firstVariant->price,
                    'discount_amount'         => 0.00,
                    'tax_amount'              => round($firstVariant->price * 0.10, 2),
                    'shipping_amount'         => $selectedShipMethod->base_rate,
                    'grand_total'             => round($firstVariant->price * 1.10 + $selectedShipMethod->base_rate, 2),
                    'currency'                => 'USD',
                    'exchange_rate'           => 1.0,
                    'base_currency'           => 'USD',
                    'base_grand_total'        => round($firstVariant->price * 1.10 + $selectedShipMethod->base_rate, 2),
                    'status'                  => 'processing',
                    'payment_status'          => 'paid',
                    'payment_method'          => 'stripe',
                    'fulfillment_status'      => 'fulfilled',
                    'shipping_method_id'      => $selectedShipMethod->id,
                    'estimated_delivery_date' => Carbon::now()->addDays(2)->toDateString(),
                    'notes'                   => 'Please leave on porch if no answer.',
                ]
            );

            OrderItem::updateOrCreate(
                ['order_id' => $order1->id, 'product_variant_id' => $firstVariant->id],
                [
                    'product_id'      => $firstVariant->product_id,
                    'product_name'    => $firstVariant->product->name,
                    'variant_sku'     => $firstVariant->sku,
                    'unit_price'      => $firstVariant->price,
                    'quantity'        => 1,
                    'discount_amount' => 0.00,
                    'tax_amount'      => round($firstVariant->price * 0.10, 2),
                    'line_total'      => $firstVariant->price,
                ]
            );

            // Active Dispatched Shipment
            Shipment::updateOrCreate(
                ['order_id' => $order1->id, 'shipment_number' => 'SHP-' . date('Ymd') . '-00101'],
                [
                    'tenant_id'             => $tenant->id,
                    'shipping_method_id'    => $selectedShipMethod->id,
                    'tracking_number'       => 'FX-9988223311',
                    'carrier'               => $selectedShipMethod->carrier,
                    'tracking_url'          => 'https://www.fedex.com/fedextrack/?trknbr=9988223311',
                    'status'                => Shipment::STATUS_IN_TRANSIT,
                    'shipped_at'            => Carbon::now()->subDay(),
                    'estimated_delivery_at' => Carbon::now()->addDay(),
                    'recipient_name'        => $order1->customer_name,
                    'delivery_address'      => $order1->shipping_address,
                    'timeline'              => [
                        [
                            'status'      => Shipment::STATUS_PENDING,
                            'description' => 'Package generated at Central Warehouse.',
                            'location'    => 'Central Logistics Hub',
                            'timestamp'   => Carbon::now()->subDays(2)->toIso8601String(),
                        ],
                        [
                            'status'      => Shipment::STATUS_DISPATCHED,
                            'description' => 'Picked up by carrier.',
                            'location'    => 'Central Logistics Hub',
                            'timestamp'   => Carbon::now()->subDay()->toIso8601String(),
                        ],
                        [
                            'status'      => Shipment::STATUS_IN_TRANSIT,
                            'description' => 'Sorted at Regional Transit Hub.',
                            'location'    => 'Portland Sorting Facility',
                            'timestamp'   => Carbon::now()->subHours(6)->toIso8601String(),
                        ],
                    ],
                    'notes'                 => 'Signature required upon delivery.',
                ]
            );
        }

        // 5. Sample Order 2: Delivered Order
        if ($customer2 && $firstVariant) {
            $order2 = Order::updateOrCreate(
                ['order_number' => 'ORD-' . date('Ymd', strtotime('-1 week')) . '-00095'],
                [
                    'tenant_id'               => $tenant->id,
                    'store_id'                => $store->id,
                    'tenant_branch_id'        => $branch->id,
                    'user_id'                 => $customer2->id,
                    'customer_name'           => $customer2->name,
                    'customer_email'          => $customer2->email,
                    'customer_phone'          => '+1 555-432-8765',
                    'shipping_address'        => [
                        'street'      => '123 Main Street, Suite 400',
                        'city'        => 'Seattle',
                        'state'       => 'WA',
                        'postal_code' => '98101',
                        'country'     => 'United States',
                    ],
                    'subtotal'                => 399.00,
                    'discount_amount'         => 25.00,
                    'tax_amount'              => 37.40,
                    'shipping_amount'         => 12.00,
                    'grand_total'             => 423.40,
                    'currency'                => 'USD',
                    'exchange_rate'           => 1.0,
                    'base_currency'           => 'USD',
                    'base_grand_total'        => 423.40,
                    'status'                  => 'completed',
                    'payment_status'          => 'paid',
                    'payment_method'          => 'paypal',
                    'fulfillment_status'      => 'fulfilled',
                    'shipping_method_id'      => $createdMethods['UPS_GROUND']->id ?? null,
                    'estimated_delivery_date' => Carbon::now()->subDays(2)->toDateString(),
                    'notes'                   => 'Delivered and signed by customer.',
                ]
            );

            Shipment::updateOrCreate(
                ['order_id' => $order2->id, 'shipment_number' => 'SHP-' . date('Ymd', strtotime('-1 week')) . '-00095'],
                [
                    'tenant_id'             => $tenant->id,
                    'shipping_method_id'    => $createdMethods['UPS_GROUND']->id ?? null,
                    'tracking_number'       => '1Z999AA10123456784',
                    'carrier'               => 'UPS',
                    'tracking_url'          => 'https://www.ups.com/track?tracknum=1Z999AA10123456784',
                    'status'                => Shipment::STATUS_DELIVERED,
                    'shipped_at'            => Carbon::now()->subDays(5),
                    'estimated_delivery_at' => Carbon::now()->subDays(2),
                    'recipient_name'        => $order2->customer_name,
                    'delivery_address'      => $order2->shipping_address,
                    'timeline'              => [
                        [
                            'status'      => Shipment::STATUS_DELIVERED,
                            'description' => 'Delivered to front porch.',
                            'location'    => 'Seattle, WA',
                            'timestamp'   => Carbon::now()->subDays(2)->toIso8601String(),
                        ],
                    ],
                    'notes'                 => 'Left in secure parcel locker.',
                ]
            );
        }

        $this->command->info('Order: Shipping Methods, Coupons, Orders, and Shipments seeded successfully.');
    }
}
