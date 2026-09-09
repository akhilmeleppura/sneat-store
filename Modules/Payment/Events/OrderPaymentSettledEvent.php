<?php

namespace Modules\Payment\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Order\Models\Order;
use Modules\Payment\Models\PaymentTransaction;

class OrderPaymentSettledEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public ?PaymentTransaction $transaction;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?PaymentTransaction $transaction = null)
    {
        $this->order = $order;
        $this->transaction = $transaction;
    }
}
