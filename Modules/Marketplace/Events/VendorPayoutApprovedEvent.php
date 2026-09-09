<?php

namespace Modules\Marketplace\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Marketplace\Models\VendorPayout;

class VendorPayoutApprovedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public VendorPayout $payout;

    /**
     * Create a new event instance.
     */
    public function __construct(VendorPayout $payout)
    {
        $this->payout = $payout;
    }
}
