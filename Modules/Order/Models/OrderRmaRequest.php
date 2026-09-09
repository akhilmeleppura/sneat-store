<?php

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\UsesTenant;

class OrderRmaRequest extends Model
{
    use UsesTenant;

    protected $table = 'order_rma_requests';

    protected $fillable = [
        'tenant_id',
        'order_id',
        'order_item_id',
        'user_id',
        'rma_number',
        'reason',
        'condition',
        'resolution_type',
        'status',
        'return_tracking_number',
        'admin_notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
