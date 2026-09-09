<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\UsesTenant;

class VendorPayout extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'marketplace_vendor_payouts';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'amount',
        'currency',
        'status',
        'payout_method',
        'transaction_reference',
        'notes',
        'processed_at',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'processed_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'completed' => '<span class="badge bg-label-success">Completed</span>',
            'approved'  => '<span class="badge bg-label-info">Approved</span>',
            'requested' => '<span class="badge bg-label-warning">Requested</span>',
            'rejected'  => '<span class="badge bg-label-danger">Rejected</span>',
            default     => '<span class="badge bg-label-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
}
