<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Services\InventoryService;
use Modules\Order\Models\Order;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $query = Order::with(['items', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest()->paginate(20);

        return view('order::admin.index', compact('orders'));
    }

    /**
     * Display the specified order details.
     */
    public function show($id)
    {
        $order = Order::with(['items.variant.product', 'branch', 'user', 'paymentTransactions', 'shippingMethod', 'shipments'])->findOrFail($id);
        $shippingMethods = \Modules\Order\Models\ShippingMethod::where('is_active', true)->get();

        return view('order::admin.show', compact('order', 'shippingMethods'));
    }

    /**
     * Update order and fulfillment status.
     */
    public function updateStatus(Request $request, $id, InventoryService $inventoryService)
    {
        $order = Order::with('items')->findOrFail($id);

        $validated = $request->validate([
            'status'             => 'required|in:pending,processing,completed,cancelled,refunded',
            'payment_status'     => 'required|in:unpaid,paid,partially_paid,refunded,failed',
            'fulfillment_status' => 'required|in:unfulfilled,fulfilled,cancelled',
        ]);

        $previousFulfillment = $order->fulfillment_status;
        $previousPayment = $order->payment_status;

        $order->update($validated);

        // If payment status transitioned to paid, trigger automated financial settlement
        if ($validated['payment_status'] === 'paid' && $previousPayment !== 'paid') {
            try {
                if (class_exists(\Modules\Payment\Services\FinancialSettlementService::class)) {
                    app(\Modules\Payment\Services\FinancialSettlementService::class)->settleOrder($order);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Settlement error for Order #{$order->order_number}: " . $e->getMessage());
            }
        }

        // If newly fulfilled, commit the reserved stock to physically deduct on-hand quantities
        if ($validated['fulfillment_status'] === 'fulfilled' && $previousFulfillment !== 'fulfilled') {
            $branchId = $order->tenant_branch_id ?? 1;
            foreach ($order->items as $item) {
                $inventoryService->commitReservedStock(
                    $item->product_variant_id,
                    $branchId,
                    $item->quantity,
                    $order->order_number
                );
            }
        }

        // If cancelled, release reserved stock
        if ($validated['status'] === 'cancelled' && $previousFulfillment === 'unfulfilled') {
            $branchId = $order->tenant_branch_id ?? 1;
            foreach ($order->items as $item) {
                $inventoryService->releaseReservedStock(
                    $item->product_variant_id,
                    $branchId,
                    $item->quantity,
                    $order->order_number
                );
            }
        }

        return redirect()->route('admin.orders.show', $order->id)
            ->with('success', "Order #{$order->order_number} status updated.");
    }
}
