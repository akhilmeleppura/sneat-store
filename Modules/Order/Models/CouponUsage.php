<?php

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\UsesTenant;

class CouponUsage extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'coupon_usages';

    protected $fillable = [
        'tenant_id',
        'coupon_id',
        'order_id',
        'user_id',
        'customer_email',
        'discount_amount',
        'currency',
    ];

    protected $casts = [
        'discount_amount' => 'float',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
