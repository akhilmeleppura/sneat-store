<?php

namespace Modules\Order\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Models\Branch;
use Modules\Context\Models\Store;
use Modules\Context\Traits\UsesTenant;

class Order extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'orders';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'tenant_branch_id',
        'user_id',
        'order_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'billing_address',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'grand_total',
        'currency',
        'exchange_rate',
        'base_currency',
        'base_grand_total',
        'coupon_code',
        'status',
        'payment_status',
        'payment_method',
        'fulfillment_status',
        'shipping_method_id',
        'estimated_delivery_date',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'shipping_address'   => 'array',
        'billing_address'    => 'array',
        'metadata'           => 'array',
        'subtotal'           => 'decimal:2',
        'discount_amount'    => 'decimal:2',
        'tax_amount'         => 'decimal:2',
        'shipping_amount'    => 'decimal:2',
        'grand_total'        => 'decimal:2',
        'exchange_rate'      => 'float',
        'base_grand_total'   => 'decimal:2',
        'estimated_delivery_date' => 'date',
        'created_at'         => 'datetime',
        'updated_at'         => 'datetime',
        'deleted_at'         => 'datetime',
    ];

    /**
     * Line items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Customer who placed this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Store where the order was placed.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Branch fulfilling the order.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'tenant_branch_id');
    }

    /**
     * Scope to pending orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Associated payment transactions.
     */
    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(\Modules\Payment\Models\PaymentTransaction::class, 'order_id');
    }

    /**
     * Latest payment transaction.
     */
    public function latestPaymentTransaction()
    {
        return $this->hasOne(\Modules\Payment\Models\PaymentTransaction::class, 'order_id')->latestOfMany();
    }

    /**
     * Coupon redemption record for this order.
     */
    public function couponUsage()
    {
        return $this->hasOne(CouponUsage::class, 'order_id');
    }

    /**
     * Coupon associated with this order.
     */
    public function coupon()
    {
        return $this->hasOneThrough(
            Coupon::class,
            CouponUsage::class,
            'order_id',
            'id',
            'id',
            'coupon_id'
        );
    }

    /**
     * Shipping method selected for this order.
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    /**
     * Fulfillment shipments for this order.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'order_id');
    }

    /**
     * Latest shipment fulfillment.
     */
    public function latestShipment()
    {
        return $this->hasOne(Shipment::class, 'order_id')->latestOfMany();
    }
}
