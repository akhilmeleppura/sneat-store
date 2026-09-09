<?php

namespace Modules\Order\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Order\Services\RmaService;

class RmaController extends Controller
{
    /**
     * Customer requests RMA return authorization.
     */
    public function request(Request $request, RmaService $rmaService): JsonResponse
    {
        $validated = $request->validate([
            'order_id'        => 'required|integer|exists:orders,id',
            'order_item_id'   => 'nullable|integer',
            'reason'          => 'required|string|max:255',
            'condition'       => 'nullable|in:unopened,opened,damaged,defective',
            'resolution_type' => 'nullable|in:refund,exchange,store_credit',
        ]);

        $rma = $rmaService->createRequest(
            Auth::id(),
            $validated['order_id'],
            $validated['reason'],
            $validated['condition'] ?? 'unopened',
            $validated['resolution_type'] ?? 'refund',
            $validated['order_item_id'] ?? null
        );

        return response()->json([
            'status'     => 'success',
            'rma_number' => $rma->rma_number,
            'message'    => "RMA request {$rma->rma_number} submitted successfully. Return authorization instructions will be emailed shortly.",
        ]);
    }
}
