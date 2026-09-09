<?php

namespace Modules\Search\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Catalog\Models\Product;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Perform a search on products.
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        // Use Laravel Scout
        $results = Product::search($query)->get();

        // Transform to minimal data for frontend
        return response()->json($results->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'thumbnail' => $product->thumbnail_url,
            ];
        }));
    }
}
