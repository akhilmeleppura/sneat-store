<?php

namespace Modules\Context\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;

class Store extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'stores';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'domain',
        'is_default',
        'status',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope a query to only active stores.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the tenant that owns the store.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get all branches belonging to this store.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class, 'store_id');
    }

    /**
     * Get the default branch for this store.
     */
    public function defaultBranch(): HasOne
    {
        return $this->hasOne(Branch::class, 'store_id')->where('is_default', true);
    }
}
