<?php

namespace Modules\Context\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Modules\Context\Facades\Context;
use Modules\Context\Models\Currency;

class CurrencyService
{
    /**
     * Seed default global currencies if none exist.
     */
    public function seedDefaults(?int $tenantId = null): void
    {
        $defaults = [
            [
                'code'            => 'USD',
                'name'            => 'US Dollar',
                'symbol'          => '$',
                'exchange_rate'   => 1.000000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => true,
                'is_active'       => true,
            ],
            [
                'code'            => 'EUR',
                'name'            => 'Euro',
                'symbol'          => '€',
                'exchange_rate'   => 0.920000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'GBP',
                'name'            => 'British Pound',
                'symbol'          => '£',
                'exchange_rate'   => 0.780000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'JPY',
                'name'            => 'Japanese Yen',
                'symbol'          => '¥',
                'exchange_rate'   => 155.000000,
                'decimal_places'  => 0,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'INR',
                'name'            => 'Indian Rupee',
                'symbol'          => '₹',
                'exchange_rate'   => 86.500000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'CAD',
                'name'            => 'Canadian Dollar',
                'symbol'          => 'C$',
                'exchange_rate'   => 1.380000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
            [
                'code'            => 'AUD',
                'name'            => 'Australian Dollar',
                'symbol'          => 'A$',
                'exchange_rate'   => 1.520000,
                'decimal_places'  => 2,
                'symbol_position' => 'before',
                'is_default'      => false,
                'is_active'       => true,
            ],
        ];

        foreach ($defaults as $currData) {
            $currData['tenant_id'] = $tenantId;

            Currency::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'code'      => $currData['code'],
                ],
                $currData
            );
        }
    }

    /**
     * Get all active currencies for current context.
     */
    public function getActiveCurrencies(): Collection
    {
        $currencies = Currency::where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get();

        if ($currencies->isEmpty()) {
            $this->seedDefaults();
            $currencies = Currency::where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->get();
        }

        return $currencies;
    }

    /**
     * Find a currency by code.
     */
    public function getCurrency(string $code): ?Currency
    {
        $code = strtoupper($code);
        return Currency::where('code', $code)->first();
    }

    /**
     * Get the default platform/store base currency.
     */
    public function getDefaultCurrency(): Currency
    {
        $default = Currency::where('is_default', true)->first();

        if (! $default) {
            $this->seedDefaults();
            $default = Currency::where('is_default', true)->first()
                ?? Currency::where('code', 'USD')->first();
        }

        return $default;
    }

    /**
     * Get current active currency from session or fallback to default.
     */
    public function getCurrentCurrency(): Currency
    {
        $sessionCode = Session::get('currency');

        if ($sessionCode) {
            $currency = $this->getCurrency($sessionCode);
            if ($currency && $currency->is_active) {
                return $currency;
            }
        }

        return $this->getDefaultCurrency();
    }

    /**
     * Set active currency in session.
     */
    public function setCurrentCurrency(string $code): bool
    {
        $currency = $this->getCurrency($code);

        if ($currency && $currency->is_active) {
            Session::put('currency', $currency->code);
            return true;
        }

        return false;
    }

    /**
     * Convert an amount between two currencies.
     */
    public function convert(float $amount, ?string $fromCode = null, ?string $toCode = null): float
    {
        $fromCurrency = $fromCode ? $this->getCurrency($fromCode) : $this->getDefaultCurrency();
        $toCurrency = $toCode ? $this->getCurrency($toCode) : $this->getCurrentCurrency();

        if (! $fromCurrency || ! $toCurrency) {
            return $amount;
        }

        if ($fromCurrency->code === $toCurrency->code) {
            return round($amount, $toCurrency->decimal_places);
        }

        // 1. Convert to base (rate = 1.0)
        $amountInBase = $fromCurrency->is_default
            ? $amount
            : ($amount / ($fromCurrency->exchange_rate > 0 ? $fromCurrency->exchange_rate : 1.0));

        // 2. Convert from base to target
        $converted = $toCurrency->is_default
            ? $amountInBase
            : ($amountInBase * $toCurrency->exchange_rate);

        return round($converted, $toCurrency->decimal_places);
    }

    /**
     * Format an amount in specified or current currency.
     */
    public function format(float $amount, ?string $currencyCode = null): string
    {
        $currency = $currencyCode ? $this->getCurrency($currencyCode) : $this->getCurrentCurrency();

        if (! $currency) {
            return '$' . number_format($amount, 2);
        }

        return $currency->format($amount);
    }

    /**
     * Format and convert an amount from base currency to current or specified currency.
     */
    public function formatConverted(float $amountInBase, ?string $targetCurrencyCode = null): string
    {
        $targetCurrency = $targetCurrencyCode ? $this->getCurrency($targetCurrencyCode) : $this->getCurrentCurrency();
        $converted = $this->convert($amountInBase, null, $targetCurrency->code);

        return $targetCurrency->format($converted);
    }

    /**
     * Update exchange rate for a currency.
     */
    public function updateExchangeRate(string $code, float $newRate): bool
    {
        $currency = $this->getCurrency($code);
        if ($currency && ! $currency->is_default) {
            $currency->update(['exchange_rate' => $newRate]);
            return true;
        }
        return false;
    }

    /**
     * Set a new default base currency and adjust rates accordingly.
     */
    public function setDefaultCurrency(string $code): bool
    {
        $newDefault = $this->getCurrency($code);
        if (! $newDefault) {
            return false;
        }

        // Unset old default
        Currency::where('is_default', true)->update(['is_default' => false]);

        // Set new default
        $newDefault->update([
            'is_default'    => true,
            'is_active'     => true,
            'exchange_rate' => 1.000000,
        ]);

        return true;
    }
}
