<?php

namespace Modules\Marketplace\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Order\Models\Order;

class VendorSaleNotification extends Notification
{
    use Queueable;

    public Order $order;
    public float $netEarnings;
    public int $itemCount;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, float $netEarnings, int $itemCount = 1)
    {
        $this->order = $order;
        $this->netEarnings = $netEarnings;
        $this->itemCount = $itemCount;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Sale Alert — Order #{$this->order->order_number}")
            ->greeting("Great news!")
            ->line("You have a new sale from Order #{$this->order->order_number} containing {$this->itemCount} of your catalog items.")
            ->line("Net earnings credited to your vendor balance: \$" . number_format($this->netEarnings, 2))
            ->action('View Orders in Vendor Hub', route('vendor.orders.index'))
            ->line('Keep up the great work!');
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'vendor_sale',
            'title'        => 'New Marketplace Sale!',
            'message'      => "Sale recorded in Order #{$this->order->order_number}. Net earnings: \$" . number_format($this->netEarnings, 2),
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'net_earnings' => $this->netEarnings,
            'url'          => route('vendor.orders.index'),
            'icon'         => 'bx-dollar-circle',
            'color'        => 'success',
        ];
    }
}
