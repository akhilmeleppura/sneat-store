<?php

namespace Modules\Order\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Models\OrderRmaRequest;
use Modules\Order\Services\RmaService;

class AdminRmaController extends Controller
{
    /**
     * Admin view of all RMA return requests.
     */
    public function index(Request $request)
    {
        $query = OrderRmaRequest::withoutTenancy()->with(['order', 'user'])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('rma_number', 'like', "%{$search}%")
                    ->orWhere('return_tracking_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('order', function ($oq) use ($search) {
                        $oq->where('order_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            $rmaRequests = $query->paginate(15);
            return response()->json([
                'status' => 'success',
                'data'   => $rmaRequests,
            ]);
        }

        $rmaRequests = $query->paginate(15)->withQueryString();

        $stats = [
            'total'    => OrderRmaRequest::withoutTenancy()->count(),
            'pending'  => OrderRmaRequest::withoutTenancy()->where('status', 'pending')->count(),
            'approved' => OrderRmaRequest::withoutTenancy()->whereIn('status', ['approved', 'label_issued'])->count(),
            'received' => OrderRmaRequest::withoutTenancy()->whereIn('status', ['received', 'inspected'])->count(),
            'resolved' => OrderRmaRequest::withoutTenancy()->where('status', 'resolved')->count(),
        ];

        return view('order::admin.rma.index', compact('rmaRequests', 'stats'));
    }

    /**
     * Update RMA status (Approve, issue return label, mark received/refunded).
     */
    public function updateStatus(Request $request, int $id, RmaService $rmaService)
    {
        $validated = $request->validate([
            'status'                 => 'required|string|in:pending,approved,label_issued,received,inspected,resolved,rejected',
            'admin_notes'            => 'nullable|string|max:500',
            'return_tracking_number' => 'nullable|string|max:100',
        ]);

        $rma = $rmaService->updateStatus(
            $id,
            $validated['status'],
            $validated['admin_notes'] ?? null,
            $validated['return_tracking_number'] ?? null
        );

        if ($request->expectsJson() && !$request->hasHeader('X-Inertia')) {
            return response()->json([
                'status'  => 'success',
                'message' => "RMA #{$rma->rma_number} status updated to {$rma->status}.",
                'rma'     => $rma,
            ]);
        }

        return redirect()->back()->with('success', "RMA #{$rma->rma_number} status updated to {$rma->status}.");
    }
}

