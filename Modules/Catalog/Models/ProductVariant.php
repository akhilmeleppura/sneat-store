<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;
use Modules\Inventory\Models\InventoryStock;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'product_variants';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'cost_price',
        'weight',
        'dimensions',
        'image_url',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Parent product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Associated attribute values.
     */
    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(AttributeValue::class, 'product_variant_attributes', 'product_variant_id', 'attribute_value_id')
            ->withPivot('attribute_id')
            ->withTimestamps();
    }

    /**
     * Multi-branch inventory stock entries.
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class, 'product_variant_id');
    }

    /**
     * Calculate total available stock across all branches.
     */
    public function getTotalAvailableStockAttribute(): int
    {
        return (int) $this->stocks()->sum('quantity_on_hand') - (int) $this->stocks()->sum('quantity_reserved');
    }

    /**
     * Format a readable string of variant attributes (e.g. "Color: Blue | Size: L").
     */
    public function getAttributeSummaryAttribute(): string
    {
        $pairs = [];
        foreach ($this->attributeValues as $val) {
            $attrName = $val->attribute?->name ?? 'Attribute';
            $pairs[] = "{$attrName}: {$val->value}";
        }

        return ! empty($pairs) ? implode(' | ', $pairs) : $this->sku;
    }

    /**
     * Get variant display name.
     */
    public function getNameAttribute(): string
    {
        return $this->attribute_summary ?: $this->sku;
    }
}

