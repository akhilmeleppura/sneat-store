<?php

namespace Modules\Order\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;

class Coupon extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'coupons';

    protected $fillable = [
        'tenant_id',
        'vendor_id',
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_limit_per_user',
        'times_used',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value'               => 'float',
        'min_order_amount'    => 'float',
        'max_discount_amount' => 'float',
        'usage_limit'         => 'integer',
        'usage_limit_per_user'=> 'integer',
        'times_used'          => 'integer',
        'starts_at'           => 'datetime',
        'expires_at'          => 'datetime',
        'is_active'           => 'boolean',
    ];

    /**
     * Auto uppercase coupon code on set.
     */
    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /**
     * Usages relationship.
     */
    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class, 'coupon_id');
    }

    /**
     * Optional vendor relationship for marketplace coupons.
     */
    public function vendor(): BelongsTo
    {
        if (class_exists(\Modules\Marketplace\Models\MarketplaceVendor::class)) {
            return $this->belongsTo(\Modules\Marketplace\Models\MarketplaceVendor::class, 'vendor_id');
        }
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Scope for active coupons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Check if coupon is currently available for redemption.
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if a specific user or email has reached per-user usage limits.
     */
    public function canBeUsedBy(?User $user = null, ?string $email = null): bool
    {
        if ($this->usage_limit_per_user === null || $this->usage_limit_per_user <= 0) {
            return true;
        }

        $query = $this->usages();

        if ($user) {
            $userUsage = (clone $query)->where('user_id', $user->id)->count();
            if ($userUsage >= $this->usage_limit_per_user) {
                return false;
            }
        }

        if ($email) {
            $emailUsage = (clone $query)->where('customer_email', strtolower(trim($email)))->count();
            if ($emailUsage >= $this->usage_limit_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount amount for a given subtotal.
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < (float) $this->min_order_amount) {
            return 0.00;
        }

        if ($this->type === 'percentage') {
            $discount = $subtotal * ($this->value / 100);
            if ($this->max_discount_amount !== null && $this->max_discount_amount > 0) {
                $discount = min($discount, (float) $this->max_discount_amount);
            }
        } else {
            // Fixed dollar discount
            $discount = min($subtotal, $this->value);
        }

        return round(max(0, $discount), 2);
    }

    /**
     * Sneat status badge HTML accessor.
     */
    public function getStatusBadgeAttribute(): string
    {
        if (! $this->is_active) {
            return '<span class="badge bg-label-secondary">Disabled</span>';
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return '<span class="badge bg-label-warning">Scheduled</span>';
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return '<span class="badge bg-label-danger">Expired</span>';
        }

        if ($this->usage_limit !== null && $this->times_used >= $this->usage_limit) {
            return '<span class="badge bg-label-dark">Depleted</span>';
        }

        return '<span class="badge bg-label-success">Active</span>';
    }

    /**
     * Type badge accessor.
     */
    public function getTypeBadgeAttribute(): string
    {
        if ($this->type === 'percentage') {
            return '<span class="badge bg-label-primary"><i class="bx bx-purchase-tag-alt me-1"></i>' . (float) $this->value . '% Off</span>';
        }

        return '<span class="badge bg-label-info"><i class="bx bx-dollar-circle me-1"></i>$' . number_format($this->value, 2) . ' Fixed</span>';
    }
}
