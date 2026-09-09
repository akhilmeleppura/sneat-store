<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\UsesTenant;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class VendorEarning extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'marketplace_vendor_earnings';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'order_id',
        'order_item_id',
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'net_amount',
        'status',
    ];

    protected $casts = [
        'gross_amount'      => 'decimal:2',
        'commission_rate'   => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount'        => 'decimal:2',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'available' => '<span class="badge bg-label-success">Available</span>',
            'withdrawn' => '<span class="badge bg-label-info">Withdrawn</span>',
            'refunded'  => '<span class="badge bg-label-danger">Refunded</span>',
            default     => '<span class="badge bg-label-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
}
