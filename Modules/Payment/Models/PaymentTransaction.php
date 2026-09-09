<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;
use Modules\Order\Models\Order;

class PaymentTransaction extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'transaction_reference',
        'gateway',
        'amount',
        'currency',
        'status',
        'payment_method_details',
        'payload',
    ];

    protected $casts = [
        'amount'                 => 'decimal:2',
        'payment_method_details' => 'array',
        'payload'                => 'array',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
        'deleted_at'             => 'datetime',
    ];

    /**
     * Associated order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope for successful transactions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'successful');
    }

    /**
     * Scope for pending transactions.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get Sneat badge HTML for status.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'successful' => '<span class="badge bg-label-success">Successful</span>',
            'pending'    => '<span class="badge bg-label-warning">Pending</span>',
            'refunded'   => '<span class="badge bg-label-info">Refunded</span>',
            'failed'     => '<span class="badge bg-label-danger">Failed</span>',
            default      => '<span class="badge bg-label-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
}
