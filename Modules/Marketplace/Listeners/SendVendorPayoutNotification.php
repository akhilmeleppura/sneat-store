<?php

namespace Modules\Marketplace\Listeners;

use Modules\Marketplace\Events\VendorPayoutApprovedEvent;
use Modules\Marketplace\Events\VendorPayoutRequestedEvent;
use Modules\Marketplace\Notifications\PayoutStatusNotification;

class SendVendorPayoutNotification
{
    /**
     * Handle payout requested event.
     */
    public function handleRequested(VendorPayoutRequestedEvent $event): void
    {
        $vendor = $event->payout->vendor;
        if ($vendor && $vendor->user) {
            $vendor->user->notify(new PayoutStatusNotification($event->payout, 'pending'));
        }
    }

    /**
     * Handle payout approved event.
     */
    public function handleApproved(VendorPayoutApprovedEvent $event): void
    {
        $vendor = $event->payout->vendor;
        if ($vendor && $vendor->user) {
            $vendor->user->notify(new PayoutStatusNotification($event->payout, 'approved'));
        }
    }
}
