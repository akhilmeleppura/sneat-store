<?php

namespace Modules\Catalog\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Context\Facades\Context;
use Modules\Inventory\Services\InventoryService;
use Modules\Marketplace\Models\Vendor;

class StorefrontController extends Controller
{
    /**
     * Marketplace storefront home page.
     */
    public function home()
    {
        $featuredCategories = Category::active()
            ->withCount('products')
            ->take(6)
            ->get();

        $featuredProducts = Product::where('status', 'published')
            ->forStore()
            ->with(['primaryImage', 'category', 'brand', 'vendor', 'variants', 'stores'])
            ->latest()
            ->take(8)
            ->get();

        $featuredVendors = class_exists(Vendor::class)
            ? Vendor::where('status', 'active')->withCount('products')->take(4)->get()
            : collect();

        return view('catalog::storefront.index', compact('featuredCategories', 'featuredProducts', 'featuredVendors'));
    }

    /**
     * Filterable marketplace catalog.
     */
    public function catalog(Request $request)
    {
        $query = Product::where('status', 'published')
            ->forStore()
            ->with(['primaryImage', 'category', 'brand', 'vendor', 'variants', 'stores']);

        // Search
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Brand filter
        if ($request->filled('brand')) {
            $query->whereHas('brand', function ($q) use ($request) {
                $q->where('slug', $request->brand);
            });
        }

        // Vendor filter
        if ($request->filled('vendor')) {
            $query->whereHas('vendor', function ($q) use ($request) {
                $q->where('slug', $request->vendor);
            });
        }

        // Price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            default      => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();

        $categories = Category::active()->withCount('products')->get();
        $brands = Brand::active()->withCount('products')->get();
        $vendors = class_exists(Vendor::class)
            ? Vendor::where('status', 'active')->withCount('products')->get()
            : collect();

        return view('catalog::storefront.catalog', compact('products', 'categories', 'brands', 'vendors'));
    }

    /**
     * Product detail page (PDP).
     */
    public function productDetail(string $slug, InventoryService $inventoryService)
    {
        $product = Product::where('slug', $slug)
            ->where('status', 'published')
            ->with(['images', 'category', 'brand', 'vendor', 'variants.attributeValues.attribute'])
            ->firstOrFail();

        $branchId = Context::branchId() ?? 1;

        // Calculate available stock for each variant
        $variantStocks = [];
        foreach ($product->variants as $variant) {
            $stock = $inventoryService->getAvailableStock($variant->id, $branchId);
            if ($stock <= 0) {
                // Check across all fulfillment branches in tenant network
                $stock = $inventoryService->getAvailableStock($variant->id);
            }
            $variantStocks[$variant->id] = $stock;
        }

        // Related products in the same category
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'published')
            ->with(['primaryImage', 'variants'])
            ->take(4)
            ->get();

        // Recommendation Engine: Track view & fetch complementary products
        $recommendationService = app(\Modules\Catalog\Services\RecommendationService::class);
        $recommendationService->trackRecentlyViewed($product->id);
        $customersAlsoBought = $recommendationService->getCustomersAlsoBought($product->id, 4);
        $recentlyViewed = $recommendationService->getRecentlyViewed(4, $product->id);

        // Customer Reviews and Rating Breakdown
        $reviewService = app(\Modules\Catalog\Services\ReviewService::class);
        $reviews = $product->approvedReviews()->with('user')->latest()->get();
        $ratingBreakdown = $reviewService->getRatingBreakdown($product->id);

        return view('catalog::storefront.show', compact(
            'product',
            'variantStocks',
            'relatedProducts',
            'customersAlsoBought',
            'recentlyViewed',
            'reviews',
            'ratingBreakdown'
        ));
    }
}
