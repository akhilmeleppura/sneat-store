<?php

namespace Modules\Order\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Order\Models\Order;

class OrderConfirmationNotification extends Notification
{
    use Queueable;

    public Order $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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
        $receiptUrl = route('account.orders.show', $this->order->order_number);

        $mail = (new MailMessage)
            ->subject("Order Confirmation — #{$this->order->order_number}")
            ->greeting("Hello {$this->order->customer_name},")
            ->line("Thank you for your purchase! We have received your order #{$this->order->order_number} and are processing it.")
            ->line("Order Total: \${$this->order->grand_total} {$this->order->currency}")
            ->line("Payment Method: " . strtoupper($this->order->payment_method))
            ->action('View Order Receipt', $receiptUrl)
            ->line('Thank you for shopping with us!');

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'         => 'order_confirmation',
            'title'        => 'Order Confirmed',
            'message'      => "Order #{$this->order->order_number} has been placed successfully for \${$this->order->grand_total}.",
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'grand_total'  => (float) $this->order->grand_total,
            'url'          => route('account.orders.show', $this->order->order_number),
            'icon'         => 'bx-package',
            'color'        => 'primary',
        ];
    }
}
