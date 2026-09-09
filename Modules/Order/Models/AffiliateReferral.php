<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\UsesTenant;

class AffiliateReferral extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'affiliate_referrals';

    protected $fillable = [
        'tenant_id',
        'affiliate_id',
        'order_id',
        'customer_email',
        'order_amount',
        'commission_rate',
        'commission_amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'order_amount'      => 'decimal:2',
        'commission_rate'   => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'paid_at'           => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class, 'affiliate_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
