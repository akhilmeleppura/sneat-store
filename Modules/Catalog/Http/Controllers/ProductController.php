<?php

namespace Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Store;
use Modules\Inventory\Services\InventoryService;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'brand', 'variants.stocks', 'primaryImage', 'stores']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(15);
        $categories = Category::active()->get();
        $brands = Brand::active()->get();

        return view('catalog::products.index', compact('products', 'categories', 'brands'));
    }

    /**
     * Show form for creating a new product.
     */
    public function create()
    {
        $categories = Category::active()->get();
        $brands = Brand::active()->get();
        $stores = Context::currentTenant() ? Context::currentTenant()->stores : Store::all();

        return view('catalog::products.create', compact('categories', 'brands', 'stores'));
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request, InventoryService $inventoryService)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'sku'              => 'nullable|string|max:100',
            'category_id'      => 'nullable|exists:categories,id',
            'brand_id'         => 'nullable|exists:brands,id',
            'price'            => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price'       => 'nullable|numeric|min:0',
            'type'             => 'required|in:simple,variable,digital',
            'short_description'=> 'nullable|string',
            'description'      => 'nullable|string',
            'status'           => 'required|in:draft,published,archived',
            'initial_stock'    => 'nullable|integer|min:0',
            'store_ids'        => 'nullable|array',
            'store_ids.*'      => 'integer|exists:stores,id',
            'store_prices'     => 'nullable|array',
        ]);

        $slug = Str::slug($validated['name']);
        // Ensure slug uniqueness
        $count = Product::withoutTenancy()->where('slug', 'like', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $validated['slug'] = $slug;
        $validated['has_variants'] = ($validated['type'] === 'variable');

        $product = Product::create($validated);

        // Multi-store channel assignments
        if (! empty($validated['store_ids'])) {
            $storeData = [];
            $storePrices = $request->input('store_prices', []);
            foreach ($validated['store_ids'] as $sId) {
                $override = ! empty($storePrices[$sId]) ? (float) $storePrices[$sId] : null;
                $storeData[$sId] = [
                    'is_visible'     => true,
                    'price_override' => $override,
                ];
            }
            $product->stores()->sync($storeData);
        }

        // Auto-create default base variant for simple product
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku'        => $validated['sku'] ?: ('SKU-' . strtoupper(Str::random(8))),
            'price'      => $validated['price'],
            'compare_at_price' => $validated['compare_at_price'] ?? null,
            'cost_price'       => $validated['cost_price'] ?? null,
            'status'     => 'active',
        ]);

        // Initialize stock if provided and branch exists
        if (! empty($validated['initial_stock']) && $validated['initial_stock'] > 0) {
            $branchId = Context::branchId() ?? 1;
            $inventoryService->adjustStock(
                $variant->id,
                $branchId,
                (int) $validated['initial_stock'],
                'initial',
                'manual',
                null,
                'Initial stock on product creation'
            );
        }

        return redirect()->route('catalog.products.index')
            ->with('success', "Product '{$product->name}' created successfully.");
    }

    /**
     * Show form for editing the specified product.
     */
    public function edit($id)
    {
        $product = Product::with(['category', 'brand', 'variants.stocks', 'stores'])->findOrFail($id);
        $categories = Category::active()->get();
        $brands = Brand::active()->get();
        $stores = Context::currentTenant() ? Context::currentTenant()->stores : Store::all();

        return view('catalog::products.edit', compact('product', 'categories', 'brands', 'stores'));
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'sku'              => 'nullable|string|max:100',
            'category_id'      => 'nullable|exists:categories,id',
            'brand_id'         => 'nullable|exists:brands,id',
            'price'            => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price'       => 'nullable|numeric|min:0',
            'short_description'=> 'nullable|string',
            'description'      => 'nullable|string',
            'status'           => 'required|in:draft,published,archived',
            'store_ids'        => 'nullable|array',
            'store_ids.*'      => 'integer|exists:stores,id',
            'store_prices'     => 'nullable|array',
        ]);

        $product->update($validated);

        // Multi-store channel assignments
        if ($request->has('store_ids')) {
            $storeData = [];
            $storePrices = $request->input('store_prices', []);
            foreach ((array) $request->input('store_ids') as $sId) {
                $override = ! empty($storePrices[$sId]) ? (float) $storePrices[$sId] : null;
                $storeData[$sId] = [
                    'is_visible'     => true,
                    'price_override' => $override,
                ];
            }
            $product->stores()->sync($storeData);
        } elseif ($request->has('sync_stores')) {
            $product->stores()->detach();
        }

        return redirect()->route('catalog.products.index')
            ->with('success', "Product '{$product->name}' updated successfully.");
    }

    /**
     * Remove the specified product.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $name = $product->name;
        $product->delete();

        return redirect()->route('catalog.products.index')
            ->with('success', "Product '{$name}' deleted successfully.");
    }
}
