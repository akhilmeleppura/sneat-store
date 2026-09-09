<?php

namespace Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\BackInStockSubscription;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Services\BackInStockService;

class AdminBackInStockController extends Controller
{
    /**
     * Dashboard of back-in-stock customer alerts & demand.
     */
    public function index(Request $request)
    {
        $query = BackInStockSubscription::withoutTenancy()->with(['product'])->latest('id');

        if ($request->filled('status')) {
            $isNotified = $request->query('status') === 'notified';
            $query->where('is_notified', $isNotified);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status' => 'success',
                'data'   => $query->paginate(20),
            ]);
        }

        $subscriptions = $query->paginate(20)->withQueryString();

        // Aggregate demand per product for unnotified subscribers
        $productsDemand = Product::withoutTenancy()
            ->withCount(['backInStockSubscriptions as waiting_count' => function ($q) {
                $q->where('is_notified', false);
            }])
            ->whereHas('backInStockSubscriptions', function ($q) {
                $q->where('is_notified', false);
            })
            ->orderByDesc('waiting_count')
            ->take(10)
            ->get();

        $stats = [
            'total_waiting'  => BackInStockSubscription::withoutTenancy()->where('is_notified', false)->count(),
            'unique_products' => BackInStockSubscription::withoutTenancy()->where('is_notified', false)->distinct('product_id')->count('product_id'),
            'total_notified' => BackInStockSubscription::withoutTenancy()->where('is_notified', true)->count(),
        ];

        return view('catalog::admin.back_in_stock.index', compact('subscriptions', 'productsDemand', 'stats'));
    }

    /**
     * Trigger restock alert dispatch to all waiting subscribers for a product.
     */
    public function notify(int $productId, BackInStockService $backInStockService)
    {
        $count = $backInStockService->notifyRestocked($productId);

        return redirect()->back()
            ->with('success', "Dispatched restock notification to {$count} waiting subscriber(s).");
    }
}
