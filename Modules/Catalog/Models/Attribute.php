<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Context\Traits\UsesTenant;

class Attribute extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'attributes';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'is_filterable',
        'order',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Values for this attribute.
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class, 'attribute_id')->orderBy('order');
    }
}
