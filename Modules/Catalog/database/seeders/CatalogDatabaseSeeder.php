<?php

namespace Modules\Catalog\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Models\AttributeValue;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductImage;
use Modules\Catalog\Models\ProductReview;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Marketplace\Models\Vendor;

class CatalogDatabaseSeeder extends Seeder
{
    /**
     * Run the Catalog module database seeds.
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

        // 1. Categories
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

        // 2. Brands
        $brandApple = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'apple'], ['name' => 'Apple', 'status' => 'active']);
        $brandSony = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'sony'], ['name' => 'Sony', 'status' => 'active']);
        $brandSamsung = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'samsung'], ['name' => 'Samsung', 'status' => 'active']);
        $brandNike = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'nike'], ['name' => 'Nike', 'status' => 'active']);
        $brandLogitech = Brand::updateOrCreate(['tenant_id' => $tenant->id, 'slug' => 'logitech'], ['name' => 'Logitech', 'status' => 'active']);

        // 3. Attributes & Attribute Values
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

        // 4. Vendors
        $vendorApex = Vendor::where('slug', 'apex-tech')->first();
        $vendorUrban = Vendor::where('slug', 'urban-lifestyle')->first();

        // 5. Products Data
        $productsData = [
            [
                'name'              => 'Apple MacBook Pro 16" M3 Max',
                'slug'              => 'macbook-pro-16-m3-max',
                'category_id'       => $catElectronics->id,
                'brand_id'          => $brandApple->id,
                'vendor_id'         => $vendorApex?->id,
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
                    ['sku' => 'MBP-16-512-GRY', 'price' => 2499.00, 'weight' => 2.15, 'attrs' => [$valSilver->id, $val512->id]],
                    ['sku' => 'MBP-16-1TB-GRY',  'price' => 2899.00, 'weight' => 2.15, 'attrs' => [$valSilver->id, $val1TB->id]],
                ],
            ],
            [
                'name'              => 'Sony WH-1000XM5 Wireless Noise-Cancelling Headphones',
                'slug'              => 'sony-wh-1000xm5-wireless-headphones',
                'category_id'       => $catAudio->id,
                'brand_id'          => $brandSony->id,
                'vendor_id'         => $vendorApex?->id,
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
                    ['sku' => 'SONY-XM5-BLK', 'price' => 399.00, 'weight' => 0.25, 'attrs' => [$valBlack->id]],
                    ['sku' => 'SONY-XM5-WHT', 'price' => 399.00, 'weight' => 0.25, 'attrs' => [$valWhite->id]],
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
                    ['sku' => 'S24U-256-BLK', 'price' => 1199.00, 'weight' => 0.23, 'attrs' => [$valBlack->id, $val256->id]],
                    ['sku' => 'S24U-512-BLK', 'price' => 1399.00, 'weight' => 0.23, 'attrs' => [$valBlack->id, $val512->id]],
                ],
            ],
            [
                'name'              => 'Nike Air Zoom Pegasus 40 Running Shoes',
                'slug'              => 'nike-air-zoom-pegasus-40',
                'category_id'       => $catApparel->id,
                'brand_id'          => $brandNike->id,
                'vendor_id'         => $vendorUrban?->id,
                'price'             => 130.00,
                'compare_at_price'  => 150.00,
                'sku'               => 'NIKE-PEG-40',
                'status'            => 'published',
                'is_featured'       => false,
                'has_variants'      => true,
                'rating_cache'      => 4.6,
                'rating_count'      => 29,
                'short_description' => 'A springy ride for every run, the Peg returns to help you accomplish your personal fitness goals.',
                'description'       => 'This version has the same responsiveness and neutral support you love, but with improved comfort in sensitive areas of your foot like the arch and toes.',
                'variants'          => [
                    ['sku' => 'NIKE-PEG40-M-BLK', 'price' => 130.00, 'weight' => 0.85, 'attrs' => [$valSizeM->id, $valBlack->id]],
                    ['sku' => 'NIKE-PEG40-L-BLK', 'price' => 130.00, 'weight' => 0.85, 'attrs' => [$valSizeL->id, $valBlack->id]],
                ],
            ],
            [
                'name'              => 'Logitech MX Master 3S Performance Wireless Mouse',
                'slug'              => 'logitech-mx-master-3s-mouse',
                'category_id'       => $catOffice->id,
                'brand_id'          => $brandLogitech->id,
                'vendor_id'         => $vendorApex?->id,
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
                    ['sku' => 'MX-3S-BLK', 'price' => 99.00, 'weight' => 0.14, 'attrs' => [$valBlack->id]],
                ],
            ],
            [
                'name'              => 'Ergonomic High-Back Executive Mesh Chair',
                'slug'              => 'ergonomic-high-back-mesh-chair',
                'category_id'       => $catOffice->id,
                'brand_id'          => null,
                'vendor_id'         => $vendorUrban?->id,
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
                    ['sku' => 'ERGO-CHAIR-BLK', 'price' => 349.00, 'weight' => 16.50, 'attrs' => [$valBlack->id]],
                ],
            ],
        ];

        foreach ($productsData as $pData) {
            $variants = $pData['variants'];
            unset($pData['variants']);

            $product = Product::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $pData['slug']],
                array_merge($pData, ['tenant_id' => $tenant->id])
            );

            // Seed primary product image
            ProductImage::firstOrCreate(
                ['tenant_id' => $tenant->id, 'product_id' => $product->id, 'is_primary' => true],
                [
                    'url'        => 'assets/img/ecommerce-images/product-' . substr(md5($product->slug), 0, 4) . '.png',
                    'alt_text'   => $product->name,
                    'sort_order' => 1,
                ]
            );

            foreach ($variants as $vData) {
                $attrs = $vData['attrs'] ?? [];
                unset($vData['attrs']);

                $variant = ProductVariant::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'sku' => $vData['sku']],
                    array_merge($vData, [
                        'tenant_id'  => $tenant->id,
                        'product_id' => $product->id,
                        'status'     => 'active',
                    ])
                );

                if (! empty($attrs)) {
                    $variant->attributeValues()->sync($attrs);
                }
            }
        }

        // 6. Verified Customer Accounts for Reviews
        $customer1 = User::updateOrCreate(
            ['email' => 'john.doe@example.com'],
            [
                'name'              => 'John Doe',
                'password'          => Hash::make('password'),
                'is_supreme_admin'  => false,
                'tenant_id'         => $tenant->id,
                'store_id'          => $store->id,
                'tenant_branch_id'  => $branch->id,
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
                'tenant_branch_id'  => $branch->id,
                'email_verified_at' => now(),
            ]
        );

        // 7. Product Reviews
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

        $this->command->info('Catalog: Categories, Brands, Attributes, Products, Variants, and Reviews seeded successfully.');
    }
}
