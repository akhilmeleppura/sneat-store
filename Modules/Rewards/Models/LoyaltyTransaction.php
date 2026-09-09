<?php

namespace Modules\Rewards\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\UsesTenant;
use Modules\Order\Models\Order;

class LoyaltyTransaction extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'loyalty_transactions';

    protected $fillable = [
        'tenant_id',
        'customer_reward_id',
        'order_id',
        'type',
        'points',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
    ];

    public function customerReward(): BelongsTo
    {
        return $this->belongsTo(CustomerReward::class, 'customer_reward_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
