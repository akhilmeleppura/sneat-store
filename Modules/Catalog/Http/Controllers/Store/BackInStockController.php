<?php

namespace Modules\Catalog\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Catalog\Services\BackInStockService;

class BackInStockController extends Controller
{
    /**
     * Subscribe to restock notification.
     */
    public function subscribe(Request $request, BackInStockService $service): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:30',
            'variant_id' => 'nullable|integer',
        ]);

        $result = $service->subscribe(
            $validated['product_id'],
            $validated['email'],
            $validated['phone'] ?? null,
            $validated['variant_id'] ?? null
        );

        return response()->json($result);
    }
}
