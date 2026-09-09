<?php

use Modules\Context\Services\CurrencyService;

if (! function_exists('currency_service')) {
    /**
     * Get the currency service instance.
     */
    function currency_service(): CurrencyService
    {
        return app(CurrencyService::class);
    }
}

if (! function_exists('money')) {
    /**
     * Convert an amount from base currency and format it in the current or target currency.
     */
    function money(float $amountInBase, ?string $currencyCode = null): string
    {
        return currency_service()->formatConverted($amountInBase, $currencyCode);
    }
}

if (! function_exists('currency_format')) {
    /**
     * Format an already-converted amount in current or target currency.
     */
    function currency_format(float $amount, ?string $currencyCode = null): string
    {
        return currency_service()->format($amount, $currencyCode);
    }
}

if (! function_exists('currency_convert')) {
    /**
     * Convert an amount between two currencies.
     */
    function currency_convert(float $amount, ?string $fromCode = null, ?string $toCode = null): float
    {
        return currency_service()->convert($amount, $fromCode, $toCode);
    }
}
