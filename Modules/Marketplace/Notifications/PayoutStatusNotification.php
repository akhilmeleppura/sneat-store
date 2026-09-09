<?php

namespace Modules\Marketplace\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Marketplace\Models\VendorPayout;

class PayoutStatusNotification extends Notification
{
    use Queueable;

    public VendorPayout $payout;
    public string $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(VendorPayout $payout, string $status)
    {
        $this->payout = $payout;
        $this->status = $status;
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
            ->subject("Vendor Payout {$this->status} — \${$this->payout->amount}")
            ->greeting("Hello Vendor Partner,")
            ->line("Your payout withdrawal request for \${$this->payout->amount} has been updated to: " . strtoupper($this->status) . ".")
            ->action('View Payouts in Vendor Hub', route('vendor.payouts.index'))
            ->line('Thank you for partnering with us.');
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type'          => 'payout_status',
            'title'         => 'Payout ' . ucfirst($this->status),
            'message'       => "Payout request for \${$this->payout->amount} has been {$this->status}.",
            'payout_id'     => $this->payout->id,
            'amount'        => (float) $this->payout->amount,
            'status'        => $this->status,
            'url'           => route('vendor.payouts.index'),
            'icon'          => 'bx-wallet',
            'color'         => $this->status === 'approved' ? 'success' : 'info',
        ];
    }
}
