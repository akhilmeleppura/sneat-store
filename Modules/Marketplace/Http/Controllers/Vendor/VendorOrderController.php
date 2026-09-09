<?php

namespace Modules\Marketplace\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Marketplace\Models\Vendor;
use Modules\Order\Models\Order;

class VendorOrderController extends Controller
{
    protected function getVendor(Request $request): Vendor
    {
        return $request->get('current_vendor')
            ?? Vendor::where('user_id', Auth::id())->firstOrFail();
    }

    /**
     * Display orders containing this vendor's items.
     */
    public function index(Request $request)
    {
        $vendor = $this->getVendor($request);

        $orders = Order::whereHas('items', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })
        ->with(['items' => function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        }])
        ->latest()
        ->paginate(15);

        return view('marketplace::vendor.orders.index', compact('vendor', 'orders'));
    }

    /**
     * Show order details scoped to vendor's items.
     */
    public function show(Request $request, int $id)
    {
        $vendor = $this->getVendor($request);

        $order = Order::where('id', $id)
            ->whereHas('items', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->with(['items' => function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            }])
            ->firstOrFail();

        $vendorSubtotal = $order->items->sum('line_total');
        $vendorEarnings = $order->items->sum('vendor_earnings_amount');

        return view('marketplace::vendor.orders.show', compact('vendor', 'order', 'vendorSubtotal', 'vendorEarnings'));
    }
}
