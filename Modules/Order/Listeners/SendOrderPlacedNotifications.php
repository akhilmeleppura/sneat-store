<?php

namespace Modules\Order\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Modules\Order\Events\OrderPlacedEvent;
use Modules\Order\Notifications\OrderConfirmationNotification;

class SendOrderPlacedNotifications
{
    /**
     * Handle the event.
     */
    public function handle(OrderPlacedEvent $event): void
    {
        $order = $event->order;

        if ($order->user) {
            $order->user->notify(new OrderConfirmationNotification($order));
        } elseif ($order->customer_email) {
            Notification::route('mail', $order->customer_email)
                ->notify(new OrderConfirmationNotification($order));
        }
    }
}
