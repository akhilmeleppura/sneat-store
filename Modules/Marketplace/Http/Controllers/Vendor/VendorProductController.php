<?php

namespace Modules\Marketplace\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Inventory\Services\InventoryService;
use Modules\Marketplace\Models\Vendor;

class VendorProductController extends Controller
{
    protected function getVendor(Request $request): Vendor
    {
        return $request->get('current_vendor')
            ?? Vendor::where('user_id', Auth::id())->firstOrFail();
    }

    /**
     * Display vendor's catalog.
     */
    public function index(Request $request)
    {
        $vendor = $this->getVendor($request);

        $products = Product::where('vendor_id', $vendor->id)
            ->with(['category', 'brand', 'variants'])
            ->latest()
            ->paginate(15);

        return view('marketplace::vendor.products.index', compact('vendor', 'products'));
    }

    /**
     * Show product creation form.
     */
    public function create(Request $request)
    {
        $vendor = $this->getVendor($request);
        $categories = Category::active()->get();
        $brands = Brand::active()->get();

        return view('marketplace::vendor.products.create', compact('vendor', 'categories', 'brands'));
    }

    /**
     * Store new product provided by vendor.
     */
    public function store(Request $request)
    {
        $vendor = $this->getVendor($request);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'category_id'    => 'nullable|exists:categories,id',
            'brand_id'       => 'nullable|exists:brands,id',
            'price'          => 'required|numeric|min:0.01',
            'sku'            => 'required|string|max:100',
            'stock_quantity' => 'required|integer|min:0',
            'description'    => 'nullable|string',
        ]);

        $tenantId = Context::id() ?? $vendor->tenant_id;
        $branchId = Context::branchId() ?? 1;

        $product = Product::create([
            'tenant_id'   => $tenantId,
            'vendor_id'   => $vendor->id,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id'    => $validated['brand_id'] ?? null,
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']) . '-' . Str::random(4),
            'sku'         => $validated['sku'],
            'price'       => $validated['price'],
            'description' => $validated['description'] ?? null,
            'status'      => 'published',
        ]);

        $variant = ProductVariant::create([
            'product_id'     => $product->id,
            'sku'            => $validated['sku'] . '-STD',
            'name'           => 'Standard',
            'price'          => $validated['price'],
            'stock_quantity' => $validated['stock_quantity'],
            'manage_stock'   => true,
        ]);

        // Adjust inventory if stock > 0
        if ($validated['stock_quantity'] > 0) {
            app(InventoryService::class)->adjustStock(
                $variant->id,
                $branchId,
                (int) $validated['stock_quantity'],
                'initial',
                'vendor_intake',
                null,
                "Vendor {$vendor->name} initial stock"
            );
        }

        return redirect()->route('vendor.products.index')
            ->with('success', "Product '{$product->name}' successfully published to your catalog.");
    }
}
