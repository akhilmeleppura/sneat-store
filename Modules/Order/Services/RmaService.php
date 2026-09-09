<?php

namespace Modules\Order\Services;

use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderRmaRequest;

class RmaService
{
    /**
     * Customer initiates an RMA return request.
     */
    public function createRequest(
        int $userId,
        int $orderId,
        string $reason,
        string $condition = 'unopened',
        string $resolution = 'refund',
        ?int $orderItemId = null
    ): OrderRmaRequest {
        $order = Order::withoutTenancy()->where('id', $orderId)->where('user_id', $userId)->firstOrFail();
        $rmaNumber = 'RMA-' . date('Ymd') . '-' . strtoupper(Str::random(4));

        return OrderRmaRequest::create([
            'tenant_id'       => $order->tenant_id ?? (\Modules\Context\Facades\Context::tenantId() ?? 1),
            'order_id'        => $order->id,
            'order_item_id'   => $orderItemId,
            'user_id'         => $userId,
            'rma_number'      => $rmaNumber,
            'reason'          => $reason,
            'condition'       => $condition,
            'resolution_type' => $resolution,
            'status'          => 'pending',
        ]);
    }

    /**
     * Admin/merchant updates RMA lifecycle status.
     */
    public function updateStatus(
        int $rmaId,
        string $newStatus,
        ?string $adminNotes = null,
        ?string $trackingNumber = null
    ): OrderRmaRequest {
        $rma = OrderRmaRequest::withoutTenancy()->findOrFail($rmaId);

        $data = ['status' => $newStatus];
        if ($adminNotes !== null) {
            $data['admin_notes'] = $adminNotes;
        }
        if ($trackingNumber !== null) {
            $data['return_tracking_number'] = $trackingNumber;
        }

        $rma->update($data);

        try {
            if ($rma->user) {
                $rma->user->notify(new \Modules\Order\Notifications\RmaStatusUpdatedNotification($rma));
            }
        } catch (\Throwable $e) {
            // Non-blocking notification dispatch
        }

        return $rma;
    }
}
