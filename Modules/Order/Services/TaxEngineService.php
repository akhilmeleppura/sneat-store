<?php

namespace Modules\Order\Services;

use Modules\Order\Models\TaxRate;

class TaxEngineService
{
    public const DEFAULT_TAX_RATE = 5.00; // 5% standard fallback

    /**
     * Calculate tax for destination address and buyer type.
     */
    public function calculateTax(float $taxableAmount, string $countryCode, ?string $stateCode = null, bool $isB2B = false): array
    {
        $country = strtoupper(trim($countryCode));
        $state   = $stateCode ? strtoupper(trim($stateCode)) : null;

        // 1. Try finding state-level rate
        $rule = null;
        if ($state) {
            $rule = TaxRate::where('country_code', $country)
                ->where('state_code', $state)
                ->where('is_active', true)
                ->first();
        }

        // 2. Try finding country-level rate
        if (!$rule) {
            $rule = TaxRate::where('country_code', $country)
                ->whereNull('state_code')
                ->where('is_active', true)
                ->first();
        }

        // 3. Check B2B exemption
        if ($isB2B && $rule && $rule->is_b2b_exempt) {
            return [
                'tax_amount' => 0.00,
                'tax_rate'   => 0.00,
                'tax_name'   => $rule->tax_name . ' (B2B Exempt)',
                'is_exempt'  => true,
            ];
        }

        $defaultRate = (float) config('order.default_tax_rate', 10.00);
        $rate = $rule ? (float) $rule->rate_percentage : $defaultRate;
        $name = $rule ? $rule->tax_name : 'Standard Sales Tax';
        $taxAmount = round(($taxableAmount * $rate) / 100, 2);

        return [
            'tax_amount' => $taxAmount,
            'tax_rate'   => $rate,
            'tax_name'   => $name,
            'is_exempt'  => false,
        ];
    }
}
