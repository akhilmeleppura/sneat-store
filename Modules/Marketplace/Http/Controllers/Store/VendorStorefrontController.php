<?php

namespace Modules\Marketplace\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\Vendor;

class VendorStorefrontController extends Controller
{
    /**
     * Display the public vendor storefront with listed products.
     */
    public function show(Request $request, string $slug)
    {
        $vendor = Vendor::where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        $query = $vendor->products()
            ->with(['variants', 'category', 'brand', 'images'])
            ->where('status', 'published')
            ->latest('id');

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('rating_count', 'desc');
                    break;
                default:
                    $query->latest('id');
                    break;
            }
        }

        $products = $query->paginate(12)->withQueryString();

        if ($request->expectsJson()) {
            return response()->json([
                'vendor'   => $vendor,
                'products' => $products,
            ]);
        }

        return view('marketplace::storefront.show', compact('vendor', 'products'));
    }
}
