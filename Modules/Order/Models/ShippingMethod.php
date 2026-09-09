<?php

namespace Modules\Order\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Context\Models\Store;
use Modules\Context\Traits\UsesTenant;

class ShippingMethod extends Model
{
    use HasFactory, SoftDeletes, UsesTenant;

    protected $table = 'shipping_methods';

    protected $fillable = [
        'tenant_id',
        'store_id',
        'name',
        'code',
        'carrier',
        'rate_type',
        'base_rate',
        'free_shipping_threshold',
        'min_days',
        'max_days',
        'description',
        'sort_order',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'base_rate'               => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'min_days'                => 'integer',
        'max_days'                => 'integer',
        'sort_order'              => 'integer',
        'is_active'               => 'boolean',
        'settings'                => 'array',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
        'deleted_at'              => 'datetime',
    ];

    /**
     * Store relationship (optional store-specific override).
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Orders using this shipping method.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'shipping_method_id');
    }

    /**
     * Shipments assigned with this carrier method.
     */
    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'shipping_method_id');
    }

    /**
     * Active scope.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    /**
     * Human-friendly delivery estimate text.
     */
    public function getEstimatedDeliveryTextAttribute(): string
    {
        if ($this->min_days === $this->max_days) {
            return $this->min_days . ' business day' . ($this->min_days > 1 ? 's' : '');
        }

        return "{$this->min_days} - {$this->max_days} business days";
    }

    /**
     * Calculate estimated delivery arrival Carbon date.
     */
    public function calculateEstimatedDeliveryDate(?Carbon $fromDate = null): Carbon
    {
        $date = ($fromDate ?: Carbon::now())->copy();
        $daysToAdd = max(1, (int) $this->max_days);

        // Add weekdays for business days
        for ($i = 0; $i < $daysToAdd; $i++) {
            $date->addDay();
            while ($date->isWeekend()) {
                $date->addDay();
            }
        }

        return $date;
    }

    /**
     * Calculate exact shipping charge based on order subtotal and parcel weight.
     */
    public function calculateRate(float $subtotal = 0.0, float $weight = 0.0): float
    {
        if ($this->rate_type === 'free') {
            return 0.00;
        }

        if ($this->free_shipping_threshold !== null && $subtotal >= (float) $this->free_shipping_threshold) {
            return 0.00;
        }

        if ($this->rate_type === 'flat') {
            return (float) $this->base_rate;
        }

        if ($this->rate_type === 'tiered_weight') {
            $tiers = $this->settings['weight_tiers'] ?? [];
            if (! empty($tiers) && is_array($tiers)) {
                // Sort ascending by max_weight
                usort($tiers, fn ($a, $b) => ($a['max_weight'] ?? 0) <=> ($b['max_weight'] ?? 0));
                foreach ($tiers as $tier) {
                    if ($weight <= (float) ($tier['max_weight'] ?? 0)) {
                        return (float) ($tier['rate'] ?? $this->base_rate);
                    }
                }
                // If heavier than all tiers, return last tier or base_rate
                $lastTier = end($tiers);
                return (float) ($lastTier['rate'] ?? $this->base_rate);
            }
            return (float) $this->base_rate;
        }

        if ($this->rate_type === 'tiered_total') {
            $tiers = $this->settings['total_tiers'] ?? [];
            if (! empty($tiers) && is_array($tiers)) {
                // Sort descending by min_total
                usort($tiers, fn ($a, $b) => ($b['min_total'] ?? 0) <=> ($a['min_total'] ?? 0));
                foreach ($tiers as $tier) {
                    if ($subtotal >= (float) ($tier['min_total'] ?? 0)) {
                        return (float) ($tier['rate'] ?? $this->base_rate);
                    }
                }
            }
            return (float) $this->base_rate;
        }

        return (float) $this->base_rate;
    }
}
