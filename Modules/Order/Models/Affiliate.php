<?php

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;

class Affiliate extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'affiliates';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'affiliate_code',
        'commission_rate',
        'total_earnings',
        'pending_earnings',
        'paid_earnings',
        'status',
        'payout_method',
        'payout_account',
    ];

    protected $casts = [
        'commission_rate'  => 'decimal:2',
        'total_earnings'   => 'decimal:2',
        'pending_earnings' => 'decimal:2',
        'paid_earnings'    => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(AffiliateReferral::class, 'affiliate_id')->latest();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get shareable storefront referral link.
     */
    public function getReferralUrlAttribute(): string
    {
        return url('/?ref=' . $this->affiliate_code);
    }

    /**
     * Calculate commission for an order subtotal.
     */
    public function calculateCommission(float $orderSubtotal): float
    {
        return round($orderSubtotal * ((float) $this->commission_rate / 100), 2);
    }
}
