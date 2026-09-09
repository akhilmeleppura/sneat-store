<?php

namespace Modules\Marketplace\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Marketplace\Models\Vendor;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class VendorDashboardController extends Controller
{
    /**
     * Resolve currently authenticated vendor.
     */
    protected function getVendor(Request $request): Vendor
    {
        return $request->get('current_vendor')
            ?? Vendor::where('user_id', Auth::id())->firstOrFail();
    }

    /**
     * Display the vendor portal dashboard.
     */
    public function index(Request $request, \Modules\Marketplace\Services\VendorAnalyticsService $analyticsService)
    {
        $vendor = $this->getVendor($request);

        $availableBalance = (float) $vendor->balance;
        $totalEarnings = (float) $vendor->earnings()->sum('net_amount');
        $totalOrders = OrderItem::where('vendor_id', $vendor->id)->distinct('order_id')->count('order_id');
        $totalProducts = $vendor->products()->count();

        $chartData = $analyticsService->getVendorChartData($vendor->id, '30_days');

        $recentEarnings = $vendor->earnings()
            ->with(['order', 'orderItem'])
            ->latest()
            ->take(5)
            ->get();

        $recentOrders = Order::whereHas('items', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })
        ->with(['items' => function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        }])
        ->latest()
        ->take(5)
        ->get();

        return view('marketplace::vendor.dashboard', compact(
            'vendor',
            'availableBalance',
            'totalEarnings',
            'totalOrders',
            'totalProducts',
            'chartData',
            'recentEarnings',
            'recentOrders'
        ));
    }
}
