<?php

namespace Modules\Cart\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Context\Traits\UsesTenant;
use Modules\Order\Models\Order;

class AbandonedCartRecovery extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'abandoned_cart_recoveries';

    protected $fillable = [
        'tenant_id',
        'cart_id',
        'user_id',
        'customer_email',
        'customer_phone',
        'cart_subtotal',
        'currency',
        'recovery_token',
        'status',
        'recovery_discount_code',
        'items_count',
        'sent_at',
        'recovered_at',
        'recovered_order_id',
    ];

    protected $casts = [
        'cart_subtotal' => 'decimal:2',
        'sent_at' => 'datetime',
        'recovered_at' => 'datetime',
    ];

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function recoveredOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'recovered_order_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRecovered($query)
    {
        return $query->where('status', 'recovered');
    }

    public function scopeReminded($query)
    {
        return $query->whereIn('status', ['first_reminder_sent', 'second_reminder_sent']);
    }

    /**
     * Generate a unique 64-character recovery token.
     */
    public static function generateToken(): string
    {
        return Str::random(40) . time();
    }

    /**
     * Get one-click recovery URL.
     */
    public function getRecoveryUrlAttribute(): string
    {
        return url("/cart/recover/{$this->recovery_token}");
    }
}
