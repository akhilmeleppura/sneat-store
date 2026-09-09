<?php

namespace Modules\Order\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderRmaRequest;
use Modules\Order\Services\RmaService;

class CustomerRmaController extends Controller
{
    /**
     * Display all customer RMA requests and return initiation options.
     */
    public function index()
    {
        $userId = Auth::id();

        $rmaRequests = OrderRmaRequest::where('user_id', $userId)
            ->with(['order.items.variant'])
            ->latest('id')
            ->paginate(10);

        $eligibleOrders = Order::where('user_id', $userId)
            ->whereIn('status', ['delivered', 'completed'])
            ->with('items.variant')
            ->latest('id')
            ->take(20)
            ->get();

        return view('order::customer.rma.index', compact('rmaRequests', 'eligibleOrders'));
    }

    /**
     * Submit a new RMA return request.
     */
    public function store(Request $request, RmaService $rmaService)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'order_id'        => 'required|integer|exists:orders,id',
            'order_item_id'   => 'nullable|integer',
            'reason'          => 'required|string|max:255',
            'condition'       => 'required|string|in:unopened,opened,damaged,defective',
            'resolution_type' => 'required|string|in:refund,exchange,store_credit',
        ]);

        // Ensure order belongs to the customer
        $order = Order::withoutTenancy()
            ->where('id', $validated['order_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        $rma = $rmaService->createRequest(
            $userId,
            $order->id,
            $validated['reason'],
            $validated['condition'],
            $validated['resolution_type'],
            $validated['order_item_id'] ?? null
        );

        if ($request->expectsJson()) {
            return response()->json([
                'status'     => 'success',
                'rma_number' => $rma->rma_number,
                'message'    => "Return authorization request {$rma->rma_number} submitted successfully.",
            ]);
        }

        return redirect()->route('account.rma.index')
            ->with('success', "Return request #{$rma->rma_number} submitted! Our support team will review it and provide return instructions.");
    }
}
