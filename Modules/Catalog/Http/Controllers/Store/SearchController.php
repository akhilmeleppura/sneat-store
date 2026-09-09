<?php

namespace Modules\Catalog\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Brand;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Services\SearchService;

class SearchController extends Controller
{
    /**
     * Storefront faceted search view.
     */
    public function search(Request $request, SearchService $searchService)
    {
        $filters = [
            'q'             => $request->query('q'),
            'category_slug' => $request->query('category'),
            'brand_id'      => $request->query('brand'),
            'min_price'     => $request->query('min_price'),
            'max_price'     => $request->query('max_price'),
            'in_stock'      => $request->boolean('in_stock'),
            'sort'          => $request->query('sort', 'latest'),
        ];

        $products = $searchService->search($filters, 12);
        $categories = Category::active()->withCount('products')->get();
        $brands = Brand::active()->withCount('products')->get();

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'status'   => 'success',
                'count'    => $products->total(),
                'products' => $products->items(),
                'html'     => view('catalog::storefront.components.product-grid-items', compact('products'))->render(),
            ]);
        }

        return view('catalog::storefront.search', compact('products', 'categories', 'brands', 'filters'));
    }

    /**
     * Fast autocomplete JSON endpoint for search bar.
     */
    public function autocomplete(Request $request, SearchService $searchService): JsonResponse
    {
        $query = $request->query('q', '');
        $results = $searchService->autocomplete($query);

        return response()->json([
            'status' => 'success',
            'data'   => $results,
        ]);
    }
}
