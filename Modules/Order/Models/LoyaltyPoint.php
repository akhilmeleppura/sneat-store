<?php

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\BelongsToTenant;

class LoyaltyPoint extends Model
{
    use BelongsToTenant;

    protected $table = 'loyalty_points_ledger';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'points_change',
        'balance_after',
        'type',
        'order_id',
        'description',
    ];

    protected $casts = [
        'points_change' => 'integer',
        'balance_after' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
