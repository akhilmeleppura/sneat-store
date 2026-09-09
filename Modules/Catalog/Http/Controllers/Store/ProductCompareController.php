<?php

namespace Modules\Catalog\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Services\ProductCompareService;

class ProductCompareController extends Controller
{
    /**
     * View side-by-side comparison page.
     */
    public function index(ProductCompareService $compareService)
    {
        $products = $compareService->getComparedProducts();

        return view('catalog::storefront.compare', compact('products'));
    }

    /**
     * Add product to compare list (AJAX).
     */
    public function add(Request $request, ProductCompareService $compareService): JsonResponse
    {
        $request->validate(['product_id' => 'required|integer|exists:products,id']);

        $result = $compareService->add($request->integer('product_id'));

        return response()->json($result);
    }

    /**
     * Remove product from compare list (AJAX).
     */
    public function remove(Request $request, ProductCompareService $compareService): JsonResponse
    {
        $request->validate(['product_id' => 'required|integer']);

        $result = $compareService->remove($request->integer('product_id'));

        return response()->json($result);
    }

    /**
     * Clear compare list.
     */
    public function clear(ProductCompareService $compareService)
    {
        $compareService->clear();

        return redirect()->back()->with('success', 'Comparison list has been cleared.');
    }
}
