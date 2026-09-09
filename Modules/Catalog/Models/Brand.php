<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;

class Brand extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'brands';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'logo',
        'website',
        'description',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to active brands.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Products belonging to this brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
