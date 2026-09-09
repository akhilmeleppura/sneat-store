<?php

namespace Modules\Order\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Order\Models\OrderRmaRequest;

class RmaStatusUpdatedNotification extends Notification
{
    use Queueable;

    public OrderRmaRequest $rma;

    /**
     * Create a new notification instance.
     */
    public function __construct(OrderRmaRequest $rma)
    {
        $this->rma = $rma;
    }

    /**
     * Delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Mail representation if enabled.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusUpper = strtoupper($this->rma->status);

        return (new MailMessage)
            ->subject("Return Request Update — #{$this->rma->rma_number} ({$statusUpper})")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your return authorization request #{$this->rma->rma_number} has been updated to: {$statusUpper}.")
            ->when($this->rma->admin_notes, function ($mail) {
                return $mail->line("Note from merchant: {$this->rma->admin_notes}");
            })
            ->when($this->rma->return_tracking_number, function ($mail) {
                return $mail->line("Return Tracking Number: {$this->rma->return_tracking_number}");
            })
            ->action('View Return Status', route('account.rma.index'))
            ->line('Thank you for shopping with Sneat Store.');
    }

    /**
     * Database notification representation for notification dropdown feed.
     */
    public function toArray(object $notifiable): array
    {
        $color = match ($this->rma->status) {
            'approved', 'refunded' => 'success',
            'rejected'             => 'danger',
            'received', 'inspected'=> 'info',
            default                => 'warning',
        };

        return [
            'type'        => 'rma_status_update',
            'title'       => "Return Request: {$this->rma->rma_number}",
            'message'     => "Your return request status is now " . ucfirst($this->rma->status) . ($this->rma->admin_notes ? " — {$this->rma->admin_notes}" : "."),
            'rma_id'      => $this->rma->id,
            'rma_number'  => $this->rma->rma_number,
            'status'      => $this->rma->status,
            'url'         => route('account.rma.index'),
            'icon'        => 'bx-revision',
            'color'       => $color,
        ];
    }
}
