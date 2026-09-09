<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Store;
use Modules\Context\Traits\UsesTenant;

class Product extends Model
{
    use HasFactory, SoftDeletes, UsesTenant, Searchable;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'brand_id',
        'vendor_id',
        'name',
        'slug',
        'sku',
        'barcode',
        'type',
        'price',
        'rating_cache',
        'rating_count',
        'compare_at_price',
        'cost_price',
        'currency',
        'short_description',
        'description',
        'status',
        'is_featured',
        'has_variants',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'rating_cache' => 'float',
        'rating_count' => 'integer',
        'compare_at_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_featured' => 'boolean',
        'has_variants' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Scope to published products.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Category relationship.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Brand relationship.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    /**
     * Product variants.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Product gallery images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('sort_order');
    }

    /**
     * Primary product image.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class, 'product_id')->where('is_primary', true);
    }

    /**
     * Multi-store assignments.
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'product_store')
            ->withPivot('is_visible', 'price_override')
            ->withTimestamps();
    }

    /**
     * Scope query to products available in a specific store (or global if not assigned to any store).
     */
    public function scopeForStore($query, ?int $storeId = null)
    {
        $storeId = $storeId ?? Context::storeId();
        if (! $storeId) {
            return $query;
        }

        return $query->where(function ($q) use ($storeId) {
            $q->whereDoesntHave('stores')
              ->orWhereHas('stores', function ($sq) use ($storeId) {
                  $sq->where('stores.id', $storeId)->where('product_store.is_visible', true);
              });
        });
    }

    /**
     * Get the effective price for a store, accounting for price overrides in product_store.
     */
    public function getEffectivePrice(?int $storeId = null): float
    {
        $storeId = $storeId ?? Context::storeId();

        if ($storeId) {
            $storeRecord = $this->relationLoaded('stores')
                ? $this->stores->firstWhere('id', $storeId)
                : $this->stores()->where('stores.id', $storeId)->first();

            if ($storeRecord && $storeRecord->pivot && $storeRecord->pivot->price_override !== null) {
                return (float) $storeRecord->pivot->price_override;
            }
        }

        return (float) $this->price;
    }

    /**
     * Get the effective price attribute for current active store.
     */
    public function getEffectivePriceAttribute(): float
    {
        return $this->getEffectivePrice();
    }

    /**
     * Alias for effective price.
     */
    public function getDisplayPriceAttribute(): float
    {
        return $this->getEffectivePrice();
    }

    /**
     * Get the display image URL (primary or first image or placeholder).
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->primaryImage && $this->primaryImage->url) {
            return $this->primaryImage->url;
        }

        $firstImage = $this->images()->first();
        if ($firstImage && $firstImage->url) {
            return $firstImage->url;
        }

        return asset('assets/img/ecommerce-images/product-placeholder.png');
    }

    /**
     * Marketplace vendor providing this product.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Marketplace\Models\Vendor::class, 'vendor_id');
    }

    /**
     * All customer reviews for this product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    /**
     * Approved reviews visible to customers.
     */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'product_id')->where('is_approved', true);
    }

    /**
     * Average rating stars HTML.
     */
    public function getStarsHtmlAttribute(): string
    {
        $rating = round($this->rating_cache);
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                $html .= '<i class="bx bxs-star text-warning"></i>';
            } else {
                $html .= '<i class="bx bx-star text-muted opacity-50"></i>';
            }
        }
        return $html;
    }

    /**
     * Back in stock customer subscriptions.
     */
    public function backInStockSubscriptions(): HasMany
    {
        return $this->hasMany(BackInStockSubscription::class, 'product_id');
    }
}

