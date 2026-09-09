<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Models\Branch;
use Modules\Context\Traits\UsesTenant;

class InventoryStock extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'inventory_stocks';

    protected $fillable = [
        'tenant_id',
        'tenant_branch_id',
        'product_id',
        'product_variant_id',
        'quantity_on_hand',
        'quantity_reserved',
        'reorder_level',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'quantity_reserved' => 'integer',
        'reorder_level' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Computed available stock (physically on-hand minus reserved for pending checkouts).
     */
    public function getQuantityAvailableAttribute(): int
    {
        return max(0, $this->quantity_on_hand - $this->quantity_reserved);
    }

    /**
     * Check if item is low in stock.
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity_on_hand <= $this->reorder_level;
    }

    /**
     * Scope to low stock items.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'reorder_level');
    }

    /**
     * Branch relationship.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'tenant_branch_id');
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

    /**
     * Audit trail transactions for this variant at this branch.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'product_variant_id', 'product_variant_id')
            ->where('tenant_branch_id', $this->tenant_branch_id);
    }
}
