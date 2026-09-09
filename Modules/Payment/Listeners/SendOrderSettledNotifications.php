<?php

namespace Modules\Payment\Listeners;

use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Notifications\VendorSaleNotification;
use Modules\Payment\Events\OrderPaymentSettledEvent;

class SendOrderSettledNotifications
{
    /**
     * Handle the event.
     */
    public function handle(OrderPaymentSettledEvent $event): void
    {
        $order = $event->order->fresh(['items']) ?? $event->order;

        if (!class_exists(Vendor::class)) {
            return;
        }

        // Group order items by vendor
        $vendorItems = $order->items->whereNotNull('vendor_id')->groupBy('vendor_id');

        foreach ($vendorItems as $vendorId => $items) {
            $vendor = Vendor::with('user')->find($vendorId);
            if (!$vendor || !$vendor->user) {
                continue;
            }

            $itemCount = $items->sum('quantity');
            $gross = (float) $items->sum('line_total');
            $commissionRate = (float) ($vendor->commission_rate ?? 10.0);
            $commissionFee = round($gross * ($commissionRate / 100), 4);
            $netEarnings = round($gross - $commissionFee, 4);

            $vendor->user->notify(new VendorSaleNotification($order, $netEarnings, $itemCount));
        }
    }
}
