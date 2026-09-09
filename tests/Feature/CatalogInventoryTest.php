<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Attribute;
use Modules\Catalog\Models\AttributeValue;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Models\Tenant;
use Modules\Inventory\Models\InventoryStock;
use Modules\Inventory\Models\InventoryTransaction;
use Modules\Inventory\Services\InventoryService;
use Tests\TestCase;

class CatalogInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Store $store;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Demo Tenant',
            'slug' => 'demotenant',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Store',
            'slug' => 'main-store',
            'is_default' => true,
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'name' => 'Central Warehouse',
            'slug' => 'central-warehouse',
            'is_default' => true,
        ]);

        Context::setTenant($this->tenant);
        Context::setStore($this->store);
        Context::setBranch($this->branch);
    }

    protected function tearDown(): void
    {
        Context::clear();
        $this->branch->forceDelete();
        $this->store->forceDelete();
        $this->tenant->forceDelete();
        parent::tearDown();
    }

    public function test_category_and_brand_creation_with_tenant_scoping(): void
    {
        $category = Category::create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        $brand = Brand::create([
            'name' => 'Apple',
            'slug' => 'apple',
        ]);

        $this->assertEquals($this->tenant->id, $category->tenant_id);
        $this->assertEquals($this->tenant->id, $brand->tenant_id);

        $category->forceDelete();
        $brand->forceDelete();
    }

    public function test_product_and_variant_creation_with_attributes(): void
    {
        $category = Category::create(['name' => 'Apparel', 'slug' => 'apparel']);
        $attribute = Attribute::create(['name' => 'Size', 'slug' => 'size', 'type' => 'select']);
        $valL = AttributeValue::create(['attribute_id' => $attribute->id, 'value' => 'L', 'label' => 'Large']);

        $product = Product::create([
            'name' => 'Cotton T-Shirt',
            'slug' => 'cotton-t-shirt',
            'type' => 'variable',
            'category_id' => $category->id,
            'has_variants' => true,
            'price' => 29.99,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TSHIRT-L',
            'price' => 29.99,
        ]);

        $variant->attributeValues()->attach($valL->id);

        $this->assertEquals($this->tenant->id, $product->tenant_id);
        $this->assertEquals($this->tenant->id, $variant->tenant_id);
        $this->assertCount(1, $product->variants);
        $this->assertEquals('Size: L', $variant->attribute_summary);

        $variant->attributeValues()->detach();
        $variant->forceDelete();
        $product->forceDelete();
        $valL->forceDelete();
        $attribute->forceDelete();
        $category->forceDelete();
    }

    public function test_inventory_service_atomic_adjust_reserve_release_commit(): void
    {
        $product = Product::create([
            'name' => 'Sneakers Pro',
            'slug' => 'sneakers-pro',
            'price' => 99.00,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SNK-42',
            'price' => 99.00,
        ]);

        $service = app(InventoryService::class);

        // 1. Initial stock adjustment (+100 units)
        $stock = $service->adjustStock(
            $variant->id,
            $this->branch->id,
            100,
            'initial',
            'po',
            'PO-1001',
            'Initial warehouse intake'
        );

        $this->assertEquals(100, $stock->quantity_on_hand);
        $this->assertEquals(0, $stock->quantity_reserved);
        $this->assertEquals(100, $stock->quantity_available);

        // Check transaction audit record
        $tx = InventoryTransaction::where('product_variant_id', $variant->id)->latest()->first();
        $this->assertEquals(100, $tx->balance_after);
        $this->assertEquals('initial', $tx->type);

        // 2. Reserve 5 units for checkout
        $reserved = $service->reserveStock($variant->id, $this->branch->id, 5, 'ORD-2026-001');
        $this->assertTrue($reserved);

        $stock->refresh();
        $this->assertEquals(100, $stock->quantity_on_hand);
        $this->assertEquals(5, $stock->quantity_reserved);
        $this->assertEquals(95, $stock->quantity_available);

        // 3. Release 2 reserved units (e.g. cart updated)
        $released = $service->releaseReservedStock($variant->id, $this->branch->id, 2, 'ORD-2026-001');
        $this->assertTrue($released);

        $stock->refresh();
        $this->assertEquals(3, $stock->quantity_reserved);
        $this->assertEquals(97, $stock->quantity_available);

        // 4. Commit remaining 3 units upon checkout payment
        $committed = $service->commitReservedStock($variant->id, $this->branch->id, 3, 'ORD-2026-001');
        $this->assertTrue($committed);

        $stock->refresh();
        $this->assertEquals(97, $stock->quantity_on_hand);
        $this->assertEquals(0, $stock->quantity_reserved);
        $this->assertEquals(97, $stock->quantity_available);

        // Clean up
        InventoryTransaction::where('product_variant_id', $variant->id)->delete();
        $stock->delete();
        $variant->forceDelete();
        $product->forceDelete();
    }

    public function test_multi_tenant_catalog_isolation(): void
    {
        // In current tenant (Demo Tenant)
        $prodA = Product::create([
            'name' => 'Demo Tenant Laptop',
            'slug' => 'demo-laptop',
            'price' => 1200.00,
        ]);

        // Create second tenant
        $otherTenant = Tenant::create(['name' => 'Competitor Tenant', 'slug' => 'competitor']);
        Context::setTenant($otherTenant);

        $prodB = Product::create([
            'name' => 'Competitor Laptop',
            'slug' => 'competitor-laptop',
            'price' => 1100.00,
        ]);

        // When in Competitor Tenant, only Competitor products are visible
        $visibleProducts = Product::all();
        $this->assertTrue($visibleProducts->contains('id', $prodB->id));
        $this->assertFalse($visibleProducts->contains('id', $prodA->id));

        // When querying withoutTenancy, all are visible
        $allProducts = Product::withoutTenancy()->get();
        $this->assertTrue($allProducts->contains('id', $prodA->id));
        $this->assertTrue($allProducts->contains('id', $prodB->id));

        // Clean up
        $prodA->forceDelete();
        $prodB->forceDelete();
        $otherTenant->forceDelete();
    }
}
