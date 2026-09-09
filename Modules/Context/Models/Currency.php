<?php

namespace Modules\Context\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Context\Traits\UsesTenant;

class Currency extends Model
{
    use HasFactory, UsesTenant;

    protected $table = 'currencies';

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'decimal_places',
        'symbol_position',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'exchange_rate'   => 'float',
        'decimal_places'  => 'integer',
        'is_default'      => 'boolean',
        'is_active'       => 'boolean',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /**
     * Scope a query to only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Format an amount in this currency.
     */
    public function format(float $amount): string
    {
        $formattedNumber = number_format($amount, $this->decimal_places, '.', ',');

        if ($this->symbol_position === 'after') {
            return "{$formattedNumber} {$this->symbol}";
        }

        return "{$this->symbol}{$formattedNumber}";
    }

    /**
     * Convert an amount from base currency (USD) to this currency.
     */
    public function convertFromBase(float $amountInBase): float
    {
        $converted = $amountInBase * $this->exchange_rate;
        return round($converted, $this->decimal_places);
    }

    /**
     * Convert an amount in this currency back to the base currency (USD).
     */
    public function convertToBase(float $amountInCurrency): float
    {
        $rate = $this->exchange_rate > 0 ? $this->exchange_rate : 1.0;
        return round($amountInCurrency / $rate, 4);
    }
}
