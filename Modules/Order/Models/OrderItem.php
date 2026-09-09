<?php

namespace Modules\Order\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'product_name',
        'variant_sku',
        'unit_price',
        'quantity',
        'tax_amount',
        'discount_amount',
        'line_total',
        'vendor_id',
        'vendor_commission_rate',
        'vendor_commission_amount',
        'vendor_earnings_amount',
    ];

    protected $casts = [
        'unit_price'               => 'decimal:2',
        'tax_amount'               => 'decimal:2',
        'discount_amount'          => 'decimal:2',
        'line_total'               => 'decimal:2',
        'vendor_commission_rate'   => 'decimal:2',
        'vendor_commission_amount' => 'decimal:2',
        'vendor_earnings_amount'   => 'decimal:2',
        'quantity'                 => 'integer',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
    ];

    /**
     * Vendor fulfilled this item.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Marketplace\Models\Vendor::class, 'vendor_id');
    }

    /**
     * Parent order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Product relationship.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Variant relationship.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
