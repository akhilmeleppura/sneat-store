<?php

namespace Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Catalog\Models\Product;
use Modules\Context\Traits\UsesTenant;

class Vendor extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'marketplace_vendors';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'slug',
        'email',
        'phone',
        'description',
        'logo_url',
        'banner_url',
        'commission_rate',
        'balance',
        'status',
        'payout_info',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'balance'         => 'decimal:2',
        'payout_info'     => 'array',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    /**
     * User account that owns this vendor profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Products listed by this vendor.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    /**
     * Earnings earned by this vendor from fulfilled orders.
     */
    public function earnings(): HasMany
    {
        return $this->hasMany(VendorEarning::class, 'vendor_id');
    }

    /**
     * Payout withdrawal requests.
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(VendorPayout::class, 'vendor_id');
    }

    /**
     * Scope for active vendors.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending vendors.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Status badge for Sneat Admin.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active'    => '<span class="badge bg-label-success">Active</span>',
            'pending'   => '<span class="badge bg-label-warning">Pending Approval</span>',
            'suspended' => '<span class="badge bg-label-danger">Suspended</span>',
            default     => '<span class="badge bg-label-secondary">' . ucfirst($this->status) . '</span>',
        };
    }
}
