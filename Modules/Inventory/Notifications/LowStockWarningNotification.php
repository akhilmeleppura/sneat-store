<?php

namespace Modules\Inventory\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Catalog\Models\ProductVariant;

class LowStockWarningNotification extends Notification
{
    use Queueable;

    public ProductVariant $variant;
    public int $branchId;
    public int $availableStock;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductVariant $variant, int $branchId, int $availableStock)
    {
        $this->variant = $variant;
        $this->branchId = $branchId;
        $this->availableStock = $availableStock;
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
        $productName = $this->variant->product?->name ?? 'Unknown Product';

        return (new MailMessage)
            ->subject("Low Stock Warning — {$this->variant->sku}")
            ->greeting("Inventory Alert")
            ->line("Product variant '{$productName}' (SKU: {$this->variant->sku}) is running low on stock.")
            ->line("Current Available Quantity: {$this->availableStock} units in Branch #{$this->branchId}.")
            ->action('Manage Catalog Stock', route('catalog.products.index'))
            ->line('Please restock soon to prevent order backorders.');
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray(object $notifiable): array
    {
        $productName = $this->variant->product?->name ?? 'Product';

        return [
            'type'            => 'low_stock_warning',
            'title'           => 'Low Stock Alert',
            'message'         => "SKU {$this->variant->sku} ({$productName}) has only {$this->availableStock} units left.",
            'variant_id'      => $this->variant->id,
            'sku'             => $this->variant->sku,
            'product_name'    => $productName,
            'available_stock' => $this->availableStock,
            'branch_id'       => $this->branchId,
            'url'             => route('catalog.products.index'),
            'icon'            => 'bx-error-alt',
            'color'           => 'warning',
        ];
    }
}
