<?php

namespace Modules\Cart\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Context\Models\Store;
use Modules\Context\Traits\UsesTenant;

class Cart extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'carts';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'user_id',
        'cart_token',
        'currency',
        'coupon_code',
        'customer_email',
        'customer_phone',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Line items in this cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    /**
     * Customer who owns this cart.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Store where the cart was created.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Computed subtotal of all items in cart.
     */
    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn(CartItem $item) => $item->line_total);
    }

    /**
     * Total item count.
     */
    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /**
     * Check if cart is empty.
     */
    public function getIsEmptyAttribute(): bool
    {
        return $this->items()->count() === 0;
    }
}
