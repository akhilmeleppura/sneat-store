<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Context\Traits\BelongsToTenant;

class BackInStockSubscription extends Model
{
    use BelongsToTenant;

    protected $table = 'back_in_stock_subscriptions';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'variant_id',
        'email',
        'phone',
        'is_notified',
        'notified_at',
    ];

    protected $casts = [
        'is_notified' => 'boolean',
        'notified_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
