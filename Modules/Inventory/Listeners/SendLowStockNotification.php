<?php

namespace Modules\Inventory\Listeners;

use App\Models\User;
use Modules\Inventory\Events\LowStockAlertEvent;
use Modules\Inventory\Notifications\LowStockWarningNotification;

class SendLowStockNotification
{
    /**
     * Handle the event.
     */
    public function handle(LowStockAlertEvent $event): void
    {
        // Find managers / administrators to alert
        $recipients = User::where('is_supreme_admin', true)
            ->orWhere('is_super_admin', true)
            ->orWhere('tenant_branch_id', $event->branchId)
            ->take(5)
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new LowStockWarningNotification(
                $event->variant,
                $event->branchId,
                $event->availableStock
            ));
        }
    }
}
