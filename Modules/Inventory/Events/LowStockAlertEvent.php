<?php

namespace Modules\Inventory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Catalog\Models\ProductVariant;

class LowStockAlertEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ProductVariant $variant;
    public int $branchId;
    public int $availableStock;

    /**
     * Create a new event instance.
     */
    public function __construct(ProductVariant $variant, int $branchId, int $availableStock)
    {
        $this->variant = $variant;
        $this->branchId = $branchId;
        $this->availableStock = $availableStock;
    }
}
