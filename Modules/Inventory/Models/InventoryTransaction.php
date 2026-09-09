<?php

namespace Modules\Inventory\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\ProductVariant;
use Modules\Context\Models\Branch;
use Modules\Context\Traits\UsesTenant;

class InventoryTransaction extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'inventory_transactions';

    protected $fillable = [
        'tenant_id',
        'tenant_branch_id',
        'product_variant_id',
        'user_id',
        'type',
        'quantity',
        'balance_after',
        'reference_type',
        'reference_id',
        'note',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'balance_after' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'tenant_branch_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
