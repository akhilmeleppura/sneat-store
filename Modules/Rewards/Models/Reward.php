<?php

namespace Modules\Rewards\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;

class Reward extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'rewards';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'description',
        'tier',
        'earn_rate',
        'redeem_rate',
        'min_points_to_redeem',
        'max_points_per_order',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'earn_rate' => 'decimal:2',
        'redeem_rate' => 'decimal:4',
        'min_points_to_redeem' => 'integer',
        'max_points_per_order' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope for active rewards.
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
     * Calculate discount amount for a given number of points.
     */
    public function calculateDiscount(int $points): float
    {
        return round($points * (float) $this->redeem_rate, 2);
    }

    /**
     * Calculate points earned for an order total.
     */
    public function calculatePointsEarned(float $subtotal): int
    {
        return (int) floor($subtotal * (float) $this->earn_rate);
    }
}
