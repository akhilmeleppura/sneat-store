<?php

namespace Modules\Catalog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Traits\UsesTenant;
use Modules\Order\Models\Order;

class ProductReview extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'product_reviews';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'user_id',
        'order_id',
        'vendor_id',
        'rating',
        'title',
        'comment',
        'is_verified_buyer',
        'is_approved',
        'admin_reply',
        'replied_at',
    ];

    protected $casts = [
        'rating'            => 'integer',
        'is_verified_buyer' => 'boolean',
        'is_approved'       => 'boolean',
        'replied_at'        => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function vendor(): BelongsTo
    {
        if (class_exists(\Modules\Marketplace\Models\MarketplaceVendor::class)) {
            return $this->belongsTo(\Modules\Marketplace\Models\MarketplaceVendor::class, 'vendor_id');
        }
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Render star rating icons HTML.
     */
    public function getStarsHtmlAttribute(): string
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $html .= '<i class="bx bxs-star text-warning"></i>';
            } else {
                $html .= '<i class="bx bx-star text-muted opacity-50"></i>';
            }
        }
        return $html;
    }

    /**
     * Sneat status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->is_approved) {
            return '<span class="badge bg-label-success"><i class="bx bx-check-circle me-1"></i>Approved</span>';
        }
        return '<span class="badge bg-label-warning"><i class="bx bx-time-five me-1"></i>Pending</span>';
    }
}
