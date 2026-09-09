<?php

namespace Modules\Order\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Order\Models\Order;

class CustomerPortalController extends Controller
{
    /**
     * Customer account overview dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();

        $ordersQuery = Order::where('user_id', $user->id);

        $totalOrders     = (clone $ordersQuery)->count();
        $completedOrders = (clone $ordersQuery)->where('status', 'completed')->count();
        $pendingOrders   = (clone $ordersQuery)->whereIn('status', ['pending', 'processing'])->count();
        $totalSpent       = (clone $ordersQuery)->where('payment_status', 'paid')->sum('grand_total');

        $recentOrders = (clone $ordersQuery)->with('items')->latest()->take(5)->get();

        return view('order::customer.dashboard', compact(
            'user',
            'totalOrders',
            'completedOrders',
            'pendingOrders',
            'totalSpent',
            'recentOrders'
        ));
    }

    /**
     * List all customer orders.
     */
    public function orders(Request $request)
    {
        $user = Auth::user();

        $query = Order::where('user_id', $user->id)->with(['items', 'shippingMethod', 'shipments'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(10)->withQueryString();

        return view('order::customer.orders.index', compact('orders'));
    }

    /**
     * Show single customer order details and receipt.
     */
    public function showOrder(string $orderNumber)
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->id)
            ->where('order_number', $orderNumber)
            ->with(['items.variant.product', 'paymentTransactions', 'shippingMethod', 'shipments'])
            ->firstOrFail();

        return view('order::customer.orders.show', compact('order'));
    }

    /**
     * Show customer profile settings.
     */
    public function profile()
    {
        $user = Auth::user();

        return view('order::customer.profile', compact('user'));
    }

    /**
     * Update customer profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile information updated successfully.');
    }

    /**
     * Render printable invoice for customer order.
     */
    public function invoice(string $orderNumber)
    {
        $user = Auth::user();

        $order = Order::where(function ($query) use ($user) {
                if ($user->hasRole('Supreme Admin') || $user->isPlatformAdmin()) {
                    return $query;
                }
                return $query->where('user_id', $user->id);
            })
            ->where('order_number', $orderNumber)
            ->with(['items.variant', 'shippingMethod', 'paymentTransactions'])
            ->firstOrFail();

        return view('order::invoice.printable', compact('order'));
    }

    /**
     * Customer requests cancellation for a pending or processing order.
     */
    public function requestCancellation(string $orderNumber, Request $request)
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        if (!in_array($order->status, ['pending', 'processing'])) {
            return back()->with('error', 'Only pending or processing orders can be cancelled. Please contact support.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $metadata = $order->metadata ?? [];
        $metadata['cancellation_requested'] = true;
        $metadata['cancellation_reason'] = $validated['reason'];
        $metadata['cancelled_at'] = now()->toISOString();

        $order->update([
            'status'   => 'cancelled',
            'metadata' => $metadata,
        ]);

        \App\Services\AuditService::log('order.cancelled', $order, "Order #{$order->order_number} cancelled by customer", ['reason' => $validated['reason']]);

        // Release reserved inventory if needed
        try {
            $inventoryService = app(\Modules\Inventory\Services\InventoryService::class);
            $branchId = \Modules\Context\Facades\Context::branchId() ?? 1;
            foreach ($order->items as $item) {
                if ($item->variant_id) {
                    $inventoryService->adjustStock(
                        $item->variant_id,
                        $branchId,
                        $item->quantity,
                        'return',
                        "Cancellation release for order #{$order->order_number}"
                    );
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback if inventory service adjustment not needed
        }

        return redirect()->back()->with('success', 'Your order #' . $order->order_number . ' has been cancelled successfully.');
    }

    /**
     * Customer requests a return / refund for a completed order.
     */
    public function requestReturn(string $orderNumber, Request $request)
    {
        $user = Auth::user();

        $order = Order::where('user_id', $user->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $metadata = $order->metadata ?? [];
        $metadata['return_requested'] = true;
        $metadata['return_reason'] = $validated['reason'];
        $metadata['return_requested_at'] = now()->toISOString();

        $order->update([
            'metadata' => $metadata,
        ]);

        \App\Services\AuditService::log('order.return_requested', $order, "Return requested for order #{$order->order_number}", ['reason' => $validated['reason']]);

        return redirect()->back()->with('success', 'Return request for order #' . $order->order_number . ' has been submitted. Our support team will review it shortly.');
    }

    /**
     * Customer loyalty rewards & points ledger.
     */
    public function loyalty(\Modules\Order\Services\LoyaltyService $loyaltyService)
    {
        $user = Auth::user();
        $balance = $loyaltyService->getUserBalance($user->id);
        $creditValue = round($balance * \Modules\Order\Services\LoyaltyService::VALUE_PER_POINT, 2);
        $ledger = \Modules\Order\Models\LoyaltyPoint::withoutTenancy()
            ->where('user_id', $user->id)
            ->with('order')
            ->latest('id')
            ->paginate(15);

        return view('order::customer.loyalty', compact('user', 'balance', 'creditValue', 'ledger'));
    }
}


