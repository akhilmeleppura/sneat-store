<?php

namespace Modules\Marketplace\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Marketplace\Models\Vendor;
use Modules\Marketplace\Models\VendorEarning;
use Modules\Order\Models\Order;

class CommissionService
{
    /**
     * Record vendor earnings and platform commissions for a settled order.
     */
    public function recordOrderEarnings(Order $order): array
    {
        $createdEarnings = [];

        DB::transaction(function () use ($order, &$createdEarnings) {
            foreach ($order->items as $item) {
                // Determine vendor from order item or associated product
                $vendorId = $item->vendor_id ?? $item->product?->vendor_id;

                if (!$vendorId) {
                    continue; // Platform-owned item
                }

                $vendor = Vendor::find($vendorId);
                if (!$vendor) {
                    continue;
                }

                // Idempotency check: don't credit twice for the same item
                $existingEarning = VendorEarning::where('order_item_id', $item->id)->first();
                if ($existingEarning) {
                    continue;
                }

                $rate = (float) ($vendor->commission_rate ?? config('marketplace.default_commission_rate', 10.00));
                $gross = (float) $item->line_total;
                $commission = round($gross * ($rate / 100), 2);
                $netEarnings = round($gross - $commission, 2);

                // 1. Create VendorEarning record
                $earning = VendorEarning::create([
                    'tenant_id'         => $order->tenant_id,
                    'vendor_id'         => $vendor->id,
                    'order_id'          => $order->id,
                    'order_item_id'     => $item->id,
                    'gross_amount'      => $gross,
                    'commission_rate'   => $rate,
                    'commission_amount' => $commission,
                    'net_amount'        => $netEarnings,
                    'status'            => 'available',
                ]);

                // 2. Increment Vendor balance
                $vendor->increment('balance', $netEarnings);

                // 3. Update OrderItem with commission attribution
                $item->update([
                    'vendor_id'                => $vendor->id,
                    'vendor_commission_rate'   => $rate,
                    'vendor_commission_amount' => $commission,
                    'vendor_earnings_amount'   => $netEarnings,
                ]);

                $createdEarnings[] = $earning;
            }
        });

        Log::info("Recorded " . count($createdEarnings) . " vendor earning allocations for Order #{$order->order_number}");

        return $createdEarnings;
    }
}
