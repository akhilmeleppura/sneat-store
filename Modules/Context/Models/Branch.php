<?php

namespace Modules\Context\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;

class Branch extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'tenant_branches';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'name',
        'slug',
        'code',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
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
     * Scope a query to only active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the store that owns the branch.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Get the tenant that owns the branch.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Approximate or configured latitude.
     */
    public function getLatitudeAttribute(): float
    {
        return (float) ($this->metadata['lat'] ?? $this->metadata['latitude'] ?? 40.7128);
    }

    /**
     * Approximate or configured longitude.
     */
    public function getLongitudeAttribute(): float
    {
        return (float) ($this->metadata['lng'] ?? $this->metadata['longitude'] ?? -74.0060);
    }

    /**
     * Formatted full physical address.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([$this->address, $this->city, $this->state, $this->postal_code, $this->country]);
        return !empty($parts) ? implode(', ', $parts) : 'Flagship Store & Distribution Warehouse';
    }
}
