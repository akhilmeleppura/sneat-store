<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Models\AttributeValue;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;
use Modules\Catalog\Models\ProductReview;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Currency;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Services\InventoryService;
use Modules\Marketplace\Models\Vendor;
use Modules\Order\Models\Coupon;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Models\Shipment;
use Modules\Order\Models\ShippingMethod;

class EcommerceStoreSeeder extends Seeder
{
    /**
     * Seed complete multi-tenant e-commerce ecosystem across all phases.
     */
    public function run(): void
    {
        $this->command->info('1. Seeding Multi-Tenant Context & Stores...');

        // 1. Tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'sneat-global'],
            ['name' => 'Sneat Global Retail']
        );

        // 2. Store
        $store = Store::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'sneat-flagship'],
            [
                'name'       => 'Sneat Flagship Store',
                'is_default' => true,
            ]
        );

        // 3. Branches / Warehouses
        $branchMain = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'central-warehouse'],
            [
                'store_id'   => $store->id,
                'name'       => 'Central Fulfillment Center',
                'code'       => 'BR-001',
                'is_default' => true,
            ]
        );

        $branchWest = Branch::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'west-coast-hub'],
            [
                'store_id'   => $store->id,
                'name'       => 'West Coast Logistics Hub',
                'code'       => 'BR-002',
                'is_default' => false,
            ]
        );

        Context::setTenant($tenant);
        Context::setStore($store);
        Context::setBranch($branchMain);

        // 4. Currencies
        $this->command->info('2. Seeding International Currencies & Exchange Rates...');
        $currencies = [
            [
                'code'          => 'USD',
                'name'          => 'US Dollar',
                'symbol'        => '$',
                'exchange_rate' => 1.0000,
                'is_default'    => true,
                'is_active'     => true,
            ],
            [
                'code'          => 'EUR',
                'name'          => 'Euro',
                'symbol'        => '€',
                'exchange_rate' => 0.9200,
                'is_default'    => false,
                'is_active'     => true,
            ],
            [
                'code'          => 'GBP',
                'name'          => 'British Pound',
                'symbol'        => '£',
                'exchange_rate' => 0.7800,
                'is_default'    => false,
                'is_active'     => true,
            ],
            [
                'code'          => 'CAD',
                'name'          => 'Canadian Dollar',
                'symbol'        => 'CA$',
                'exchange_rate' => 1.3500,
                'is_default'    => false,
                'is_active'     => true,
            ],
        ];

        foreach ($currencies as $curr) {
            Currency::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $curr['code']],
                $curr
            );
        }

        // 5. Users & Roles
        $this->command->info('3. Seeding User Accounts (Admin, Vendors, Customers)...');
        $admin = User::updateOrCreate(
            ['email' => 'admin@sneat.test'],
            [
                'name'              => 'Supreme Admin',
                'password'          => Hash::make('Admin@123'),
                'is_supreme_admin'  => true,
                'tenant_id'         => $tenant->id,
                'store_id'          => $store->id,
                'tenant_branch_id'  => $branchMain->id,
                'email_verified_at' => now(),
            ]
        );

        $vendorUser = User::updateOrCreate(
            ['email' => 'vendor@sneat.test'],
            [
                'name'              => 'Apex Vendor Partner',
                'password'          => Hash::make('password'),
                'is_supreme_admin'  => false,
                'tenant_id'         => $tenant->id,
                'store_id'          => $store->id,
                'tenant_branch_id'  => $branchMain->id,
                'email_verified_at' => now(),
            ]
        );

        $customer1 = User::updateOrCreate(
            ['email' => 'john.doe@example.com'],
            [
                'name'              => 'John Doe',
                'password'          => Hash::make('password'),
                'is_supreme_admin'  => false,
                'tenant_id'         => $tenant->id,
                'store_id'          => $store->id,
                'tenant_branch_id'  => $branchMain->id,
                'email_verified_at' => now(),
            ]
        );

        $customer2 = User::updateOrCreate(
            ['email' => 'jane.smith@example.com'],
            [
                'name'              => 'Jane Smith',
                'password'          => Hash::make('password'),
                'is_supreme_admin'  => false,
                'tenant_id'         => $tenant->id,
                'store_id'          => $store->id,
                'tenant_branch_id'  => $branchMain->id,
                'email_verified_at' => now(),
            ]
        );

        // 6. Marketplace Vendors
        $this->command->info('4. Seeding Marketplace Vendors...');
        $vendor1 = Vendor::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'apex-tech'],
            [
                'user_id'         => $vendorUser->id,
                'name'            => 'Apex Tech Solutions',
                'email'           => 'apex@marketplace.test',
                'phone'           => '+1 555-019-2834',
                'description'     => 'Authorized distributor of high-performance gaming gear and pro audio electronics.',
                'commission_rate' => 12.50,
                'balance'         => 1850.00,
                'status'          => 'active',
            ]
        );

        $vendor2 = Vendor::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'urban-lifestyle'],
            [
                'user_id'         => $admin->id,
                'name'            => 'Urban Lifestyle Goods',
                'email'           => 'urban@marketplace.test',
                'phone'           => '+1 555-014-9922',
                'description'     => 'Contemporary ergonomic workspace furniture, premium footwear, and everyday essentials.',
                'commission_rate' => 10.00,
                'balance'         => 720.00,
                'status'          => 'active',
            ]
        );

        // 7. Categories & Brands
        $this->command->info('5. Seeding Product Categories & Brands...');
        $catElectronics = Category::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'electronics-gadgets'],
            ['name' => 'Electronics & Laptops', 'status' => 'active']
        );

        $catAudio = Category::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'audio-wearables'],
            ['name' => 'Audio & Headphones', 'status' => 'active']
        );

        $catSmartphones = Category::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'smartphones-accessories'],
            ['name' => 'Smartphones & Mobile', 'status' => 'active']
        );

        $catApparel = Category::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'footwear-apparel'],
            ['name' => 'Footwear & Apparel', 'status' => 'active']
        );

        $catOffice = Category::updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'home-office-gaming'],
            ['name' => 'Office & Gaming Setup', 'status' => 'active']
        );

        $brandApple = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'apple'], ['name' => 'Apple', 'status' => 'active']);
        $brandSony = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'sony'], ['name' => 'Sony', 'status' => 'active']);
        $brandSamsung = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'samsung'], ['name' => 'Samsung', 'status' => 'active']);
        $brandNike = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'nike'], ['name' => 'Nike', 'status' => 'active']);
        $brandLogitech = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'logitech'], ['name' => 'Logitech', 'status' => 'active']);

        // 8. Attributes & Attribute Values
        $this->command->info('6. Seeding Product Attributes...');
        $attrColor = Attribute::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'color'], ['name' => 'Color', 'type' => 'select']);
        $attrStorage = Attribute::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'storage'], ['name' => 'Storage', 'type' => 'select']);
        $attrSize = Attribute::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'size'], ['name' => 'Size', 'type' => 'select']);

        $valBlack = AttributeValue::updateOrCreate(['attribute_id' => $attrColor->id, 'value' => 'Midnight Black'], ['label' => 'Midnight Black']);
        $valSilver = AttributeValue::updateOrCreate(['attribute_id' => $attrColor->id, 'value' => 'Space Gray'], ['label' => 'Space Gray']);
        $valWhite = AttributeValue::updateOrCreate(['attribute_id' => $attrColor->id, 'value' => 'Cloud White'], ['label' => 'Cloud White']);

        $val256 = AttributeValue::updateOrCreate(['attribute_id' => $attrStorage->id, 'value' => '256GB'], ['label' => '256GB NVMe']);
        $val512 = AttributeValue::updateOrCreate(['attribute_id' => $attrStorage->id, 'value' => '512GB'], ['label' => '512GB NVMe']);
        $val1TB = AttributeValue::updateOrCreate(['attribute_id' => $attrStorage->id, 'value' => '1TB'], ['label' => '1TB NVMe']);

        $valSizeM = AttributeValue::updateOrCreate(['attribute_id' => $attrSize->id, 'value' => 'M'], ['label' => 'Medium (M)']);
        $valSizeL = AttributeValue::updateOrCreate(['attribute_id' => $attrSize->id, 'value' => 'L'], ['label' => 'Large (L)']);

        // 9. Products, Variants & Physical Stock
        $this->command->info('7. Seeding Products, Variants & Inventory Stock...');
        $inventoryService = app(InventoryService::class);

        $productsData = [
            [
                'name'              => 'Apple MacBook Pro 16" M3 Max',
                'slug'              => 'macbook-pro-16-m3-max',
                'category_id'       => $catElectronics->id,
                'brand_id'          => $brandApple->id,
                'vendor_id'         => $vendor1->id,
                'price'             => 2499.00,
                'compare_at_price'  => 2799.00,
                'sku'               => 'MBP-16-M3',
                'status'            => 'published',
                'is_featured'       => true,
                'has_variants'      => true,
                'rating_cache'      => 4.9,
                'rating_count'      => 48,
                'short_description' => 'The ultimate pro laptop featuring the revolutionary M3 Max chip with 16-core CPU and 40-core GPU.',
                'description'       => 'Engineered for extreme performance, the MacBook Pro 16" delivers unprecedented computing power, breathtaking Liquid Retina XDR display, and up to 22 hours of battery life.',
                'variants'          => [
                    ['sku' => 'MBP-16-512-GRY', 'price' => 2499.00, 'weight' => 2.15, 'stock' => 25, 'attrs' => [$valSilver->id, $val512->id]],
                    ['sku' => 'MBP-16-1TB-GRY',  'price' => 2899.00, 'weight' => 2.15, 'stock' => 15, 'attrs' => [$valSilver->id, $val1TB->id]],
                ],
            ],
            [
                'name'              => 'Sony WH-1000XM5 Wireless Noise-Cancelling Headphones',
                'slug'              => 'sony-wh-1000xm5-wireless-headphones',
                'category_id'       => $catAudio->id,
                'brand_id'          => $brandSony->id,
                'vendor_id'         => $vendor1->id,
                'price'             => 399.00,
                'compare_at_price'  => 449.00,
                'sku'               => 'SONY-XM5',
                'status'            => 'published',
                'is_featured'       => true,
                'has_variants'      => true,
                'rating_cache'      => 4.8,
                'rating_count'      => 62,
                'short_description' => 'Industry-leading noise cancelation with two processors and eight microphones for unprecedented audio clarity.',
                'description'       => 'Experience magnificent sound quality engineered with the new Integrated Processor V1, supporting High-Resolution Audio Wireless and ultra-clear hands-free calling.',
                'variants'          => [
                    ['sku' => 'SONY-XM5-BLK', 'price' => 399.00, 'weight' => 0.25, 'stock' => 40, 'attrs' => [$valBlack->id]],
                    ['sku' => 'SONY-XM5-WHT', 'price' => 399.00, 'weight' => 0.25, 'stock' => 30, 'attrs' => [$valWhite->id]],
                ],
            ],
            [
                'name'              => 'Samsung Galaxy S24 Ultra 5G',
                'slug'              => 'samsung-galaxy-s24-ultra',
                'category_id'       => $catSmartphones->id,
                'brand_id'          => $brandSamsung->id,
                'vendor_id'         => null,
                'price'             => 1199.00,
                'compare_at_price'  => 1299.00,
                'sku'               => 'S24U-TITANIUM',
                'status'            => 'published',
                'is_featured'       => true,
                'has_variants'      => true,
                'rating_cache'      => 4.7,
                'rating_count'      => 34,
                'short_description' => 'Titanium armor frame, built-in S Pen, and Galaxy AI power with a 200MP quad-telephoto camera system.',
                'description'       => 'Meet Galaxy S24 Ultra, the ultimate form of Galaxy Ultra with a new titanium exterior and a 6.8-inch flat display. It is an absolute marvel of modern smartphone engineering.',
                'variants'          => [
                    ['sku' => 'S24U-256-BLK', 'price' => 1199.00, 'weight' => 0.23, 'stock' => 50, 'attrs' => [$valBlack->id, $val256->id]],
                    ['sku' => 'S24U-512-BLK', 'price' => 1399.00, 'weight' => 0.23, 'stock' => 20, 'attrs' => [$valBlack->id, $val512->id]],
                ],
            ],
            [
                'name'              => 'Nike Air Zoom Pegasus 40 Running Shoes',
                'slug'              => 'nike-air-zoom-pegasus-40',
                'category_id'       => $catApparel->id,
                'brand_id'          => $brandNike->id,
                'vendor_id'         => $vendor2->id,
                'price'             => 130.00,
                'compare_at_price'  => 150.00,
                'sku'               => 'NIKE-PEG-40',
                'status'            => 'published',
                'is_featured'       => false,
                'has_variants'      => true,
                'rating_cache'      => 4.6,
                'rating_count'      => 29,
                'short_description' => 'A springy ride for every run, the Peg return to help you accomplish your personal fitness goals.',
                'description'       => 'This version has the same responsiveness and neutral support you love, but with improved comfort in sensitive areas of your foot like the arch and toes.',
                'variants'          => [
                    ['sku' => 'NIKE-PEG40-M-BLK', 'price' => 130.00, 'weight' => 0.85, 'stock' => 35, 'attrs' => [$valSizeM->id, $valBlack->id]],
                    ['sku' => 'NIKE-PEG40-L-BLK', 'price' => 130.00, 'weight' => 0.85, 'stock' => 45, 'attrs' => [$valSizeL->id, $valBlack->id]],
                ],
            ],
            [
                'name'              => 'Logitech MX Master 3S Performance Wireless Mouse',
                'slug'              => 'logitech-mx-master-3s-mouse',
                'category_id'       => $catOffice->id,
                'brand_id'          => $brandLogitech->id,
                'vendor_id'         => $vendor1->id,
                'price'             => 99.00,
                'compare_at_price'  => 119.00,
                'sku'               => 'MX-MASTER-3S',
                'status'            => 'published',
                'is_featured'       => true,
                'has_variants'      => true,
                'rating_cache'      => 4.9,
                'rating_count'      => 85,
                'short_description' => 'Quiet clicks and 8K DPI track-on-glass sensor for elite precision and ergonomics.',
                'description'       => 'Meet MX Master 3S – an iconic mouse remastered for ultimate tactile precision, performance, and workflow speed.',
                'variants'          => [
                    ['sku' => 'MX-3S-BLK', 'price' => 99.00, 'weight' => 0.14, 'stock' => 75, 'attrs' => [$valBlack->id]],
                ],
            ],
            [
                'name'              => 'Ergonomic High-Back Executive Mesh Chair',
                'slug'              => 'ergonomic-high-back-mesh-chair',
                'category_id'       => $catOffice->id,
                'brand_id'          => null,
                'vendor_id'         => $vendor2->id,
                'price'             => 349.00,
                'compare_at_price'  => 429.00,
                'sku'               => 'ERGO-CHAIR-PRO',
                'status'            => 'published',
                'is_featured'       => false,
                'has_variants'      => true,
                'rating_cache'      => 4.5,
                'rating_count'      => 17,
                'short_description' => 'Breathable mesh lumbar support with 4D adjustable armrests for all-day office comfort.',
                'description'       => 'Engineered with premium German mesh upholstery, dynamic sync-tilt mechanism, and pneumatic height adjustments.',
                'variants'          => [
                    ['sku' => 'ERGO-CHAIR-BLK', 'price' => 349.00, 'weight' => 16.50, 'stock' => 20, 'attrs' => [$valBlack->id]],
                ],
            ],
        ];

        $allCreatedVariants = [];

        foreach ($productsData as $pData) {
            $variants = $pData['variants'];
            unset($pData['variants']);

            $product = Product::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $pData['slug']],
                array_merge($pData, ['tenant_id' => $tenant->id])
            );

            // Seed primary product image placeholder
            ProductImage::firstOrCreate(
                ['tenant_id' => $tenant->id, 'product_id' => $product->id, 'is_primary' => true],
                [
                    'url'        => 'assets/img/ecommerce-images/product-' . substr(md5($product->slug), 0, 4) . '.png',
                    'alt_text'   => $product->name,
                    'sort_order' => 1,
                ]
            );

            // Assign product to default flagship store
            $product->stores()->syncWithoutDetaching([
                $store->id => [
                    'is_visible'     => true,
                    'price_override' => null,
                ],
            ]);

            foreach ($variants as $vData) {
                $attrs = $vData['attrs'] ?? [];
                $stockQty = $vData['stock'] ?? 20;
                unset($vData['attrs'], $vData['stock']);

                $variant = ProductVariant::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'sku' => $vData['sku']],
                    array_merge($vData, [
                        'tenant_id'  => $tenant->id,
                        'product_id' => $product->id,
                        'status'     => 'active',
                    ])
                );

                $allCreatedVariants[] = $variant;

                // Sync attribute values
                if (! empty($attrs)) {
                    $variant->attributeValues()->sync($attrs);
                }

                // Initial physical inventory stock intake
                $inventoryService->adjustStock(
                    $variant->id,
                    $branchMain->id,
                    $stockQty,
                    'initial',
                    'init',
                    null,
                    "Initial seeder stock intake for {$product->name}"
                );
            }
        }

        // 10. Shipping Methods
        $this->command->info('8. Seeding Shipping Carriers & Fulfillment Methods...');
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
            $createdMethods[] = ShippingMethod::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $m['code']],
                array_merge($m, ['tenant_id' => $tenant->id])
            );
        }

        // 11. Promotional Coupons
        $this->command->info('9. Seeding Coupons & Marketing Promotions...');
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
                'usage_limit'          => 2000,
                'usage_limit_per_user' => 2,
                'is_active'            => true,
            ],
        ];

        foreach ($coupons as $c) {
            Coupon::updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $c['code']],
                array_merge($c, ['tenant_id' => $tenant->id])
            );
        }

        // 12. Product Customer Reviews
        $this->command->info('10. Seeding Product Customer Reviews & Ratings...');
        $sampleReviews = [
            [
                'product' => 'macbook-pro-16-m3-max',
                'user_id' => $customer1->id,
                'rating'  => 5,
                'title'   => 'Absolute powerhouse for software engineering & 4K video rendering',
                'comment' => 'The battery life is astonishing. Compiling large codebases happens in seconds without a single fan noise.',
            ],
            [
                'product' => 'sony-wh-1000xm5-wireless-headphones',
                'user_id' => $customer2->id,
                'rating'  => 5,
                'title'   => 'Best active noise cancellation on the market',
                'comment' => 'Used on three international flights. Silence is unreal and the ear cushions are remarkably lightweight.',
            ],
            [
                'product' => 'samsung-galaxy-s24-ultra',
                'user_id' => $customer1->id,
                'rating'  => 5,
                'title'   => 'Stunning flat display and camera zoom',
                'comment' => 'Night photography and 100x zoom are incredible. The titanium construction feels so solid in hand.',
            ],
        ];

        foreach ($sampleReviews as $rev) {
            $p = Product::where('slug', $rev['product'])->first();
            if ($p) {
                ProductReview::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'product_id' => $p->id, 'user_id' => $rev['user_id']],
                    [
                        'rating'            => $rev['rating'],
                        'title'             => $rev['title'],
                        'comment'           => $rev['comment'],
                        'is_verified_buyer' => true,
                        'is_approved'       => true,
                    ]
                );
            }
        }

        // 13. Sample Live Orders, Shipments & Fulfillment
        $this->command->info('11. Seeding Demonstration Orders & Fulfillment Shipments...');
        $selectedShipMethod = $createdMethods[0]; // FedEx Overnight

        // Create Order 1: Dispatched Shipment
        $order1 = Order::updateOrCreate(
            ['order_number' => 'ORD-' . date('Ymd') . '-00101'],
            [
                'tenant_id'               => $tenant->id,
                'store_id'                => $store->id,
                'tenant_branch_id'        => $branchMain->id,
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
                'subtotal'                => 2499.00,
                'discount_amount'         => 0.00,
                'tax_amount'              => 249.90,
                'shipping_amount'         => 35.00,
                'grand_total'             => 2783.90,
                'currency'                => 'USD',
                'exchange_rate'           => 1.0,
                'base_currency'           => 'USD',
                'base_grand_total'        => 2783.90,
                'status'                  => 'processing',
                'payment_status'          => 'paid',
                'payment_method'          => 'stripe',
                'fulfillment_status'      => 'fulfilled',
                'shipping_method_id'      => $selectedShipMethod->id,
                'estimated_delivery_date' => Carbon::now()->addDays(2)->toDateString(),
                'notes'                   => 'Please leave on porch if no answer.',
            ]
        );

        if (! empty($allCreatedVariants)) {
            $v1 = $allCreatedVariants[0];
            OrderItem::updateOrCreate(
                ['order_id' => $order1->id, 'product_variant_id' => $v1->id],
                [
                    'product_id'      => $v1->product_id,
                    'product_name'    => $v1->product->name,
                    'variant_sku'     => $v1->sku,
                    'unit_price'      => $v1->price,
                    'quantity'        => 1,
                    'discount_amount' => 0.00,
                    'tax_amount'      => 249.90,
                    'line_total'      => $v1->price,
                ]
            );
        }

        // Create Active Dispatched Shipment for Order 1
        $shipment1 = Shipment::updateOrCreate(
            ['order_id' => $order1->id, 'shipment_number' => 'SHP-' . date('Ymd') . '-00101'],
            [
                'tenant_id'             => $tenant->id,
                'shipping_method_id'    => $selectedShipMethod->id,
                'tracking_number'       => 'FX-9988223311',
                'carrier'               => 'FedEx',
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
                        'description' => 'Picked up by FedEx Express courier.',
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

        // 13. Seed Regional Tax Rates
        $this->command->info('13. Seeding Enterprise Tax Engine Rates...');
        \Modules\Order\Models\TaxRate::firstOrCreate(
            ['tenant_id' => $tenant->id, 'country_code' => 'US', 'state_code' => 'NY'],
            [
                'tax_name'        => 'New York State Sales Tax',
                'rate_percentage' => 8.875,
                'is_b2b_exempt'   => true,
                'is_active'       => true,
            ]
        );
        \Modules\Order\Models\TaxRate::firstOrCreate(
            ['tenant_id' => $tenant->id, 'country_code' => 'US', 'state_code' => 'CA'],
            [
                'tax_name'        => 'California State Sales Tax',
                'rate_percentage' => 7.25,
                'is_b2b_exempt'   => true,
                'is_active'       => true,
            ]
        );
        \Modules\Order\Models\TaxRate::firstOrCreate(
            ['tenant_id' => $tenant->id, 'country_code' => 'GB', 'state_code' => null],
            [
                'tax_name'        => 'United Kingdom Standard VAT',
                'rate_percentage' => 20.00,
                'is_b2b_exempt'   => true,
                'is_active'       => true,
            ]
        );

        // 14. Seed Demo Gift Cards
        $this->command->info('14. Seeding Demo Gift Cards...');
        \Modules\Order\Models\GiftCard::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'GIFT-SNEAT-50'],
            [
                'initial_balance' => 50.00,
                'current_balance' => 50.00,
                'currency'        => 'USD',
                'recipient_email' => 'customer@sneat.test',
                'is_active'       => true,
                'expires_at'      => Carbon::now()->addYear(),
            ]
        );
        \Modules\Order\Models\GiftCard::firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'GIFT-VIP-100'],
            [
                'initial_balance' => 100.00,
                'current_balance' => 100.00,
                'currency'        => 'USD',
                'recipient_email' => 'customer@sneat.test',
                'is_active'       => true,
                'expires_at'      => Carbon::now()->addYear(),
            ]
        );

        // 15. Seed Loyalty Points
        $this->command->info('15. Seeding Customer Loyalty Tier Points...');
        $customerUser = User::whereIn('email', ['customer@sneat.test', 'john.doe@example.com'])->first();
        if ($customerUser) {
            \Modules\Order\Models\LoyaltyPoint::firstOrCreate(
                ['tenant_id' => $tenant->id, 'user_id' => $customerUser->id, 'type' => 'order_reward'],
                [
                    'points_change' => 250,
                    'balance_after' => 250,
                    'description'   => 'Welcome bonus points & initial order purchase reward',
                ]
            );
        }

        // 16. Seed Demo Return Authorizations (RMA)
        $this->command->info('16. Seeding Demo Return Authorizations (RMA)...');
        \Modules\Order\Models\OrderRmaRequest::firstOrCreate(
            ['tenant_id' => $tenant->id, 'rma_number' => 'RMA-2026-DEMO01'],
            [
                'order_id'               => $order1->id,
                'user_id'                => $customer1->id,
                'reason'                 => 'Wrong color variant delivered',
                'condition'              => 'unopened',
                'resolution_type'        => 'exchange',
                'status'                 => 'approved',
                'return_tracking_number' => 'TRK-RET-448822',
                'admin_notes'            => 'Pre-paid return shipping label emailed to customer.',
            ]
        );

        // 17. Seed Demo B2B RFQ Quotes
        $this->command->info('17. Seeding Demo B2B Quotations (RFQ)...');
        \Modules\Order\Models\RfqQuote::firstOrCreate(
            ['tenant_id' => $tenant->id, 'quote_number' => 'RFQ-2026-DEMO01'],
            [
                'user_id'        => $customer1->id,
                'company_name'   => 'Acme Enterprise Solutions Inc.',
                'contact_name'   => 'Alex Morgan',
                'contact_email'  => 'alex.morgan@acmesolutions.test',
                'contact_phone'  => '+1 (555) 902-1144',
                'tax_id'         => 'US-EIN-987654321',
                'items_payload'  => [
                    [
                        'name'       => 'Enterprise Workstation Node',
                        'sku'        => 'HW-PRO-MAX',
                        'quantity'   => 25,
                        'unit_price' => 1850.00,
                        'line_total' => 46250.00,
                    ]
                ],
                'quoted_total'   => 46250.00,
                'status'         => 'quoted',
                'valid_until'    => Carbon::now()->addDays(30),
                'notes'          => 'Volume corporate refresh quotation with priority support.',
            ]
        );

        // 18. Seed Demo Back-In-Stock Subscriptions
        $this->command->info('18. Seeding Demo Back-In-Stock Waitlists...');
        $firstProduct = Product::first();
        if ($firstProduct) {
            \Modules\Catalog\Models\BackInStockSubscription::firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => 'demo.waitlist@sneat.test', 'product_id' => $firstProduct->id],
                [
                    'phone'       => '+1 555-443-2211',
                    'is_notified' => false,
                ]
            );
        }

        // 19. Seed Demo Newsletter Subscribers
        $this->command->info('19. Seeding Demo Newsletter Audience...');
        \Modules\General\Models\NewsletterSubscriber::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'subscriber.vip@example.com'],
            [
                'status'      => 'subscribed',
                'token'       => Str::random(32),
                'verified_at' => Carbon::now()->subWeeks(2),
                'ip_address'  => '127.0.0.1',
            ]
        );

        $this->command->info('✅ Master E-Commerce Ecosystem Seeding Completed Successfully!');
    }
}
