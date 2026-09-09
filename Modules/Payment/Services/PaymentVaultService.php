<?php

namespace Modules\Payment\Services;

use Illuminate\Support\Collection;
use Modules\Payment\Models\CustomerPaymentMethod;

class PaymentVaultService
{
    /**
     * Store tokenized payment method without saving raw card data.
     */
    public function saveToken(
        int $userId,
        string $gateway,
        string $token,
        ?string $brand = 'Visa',
        ?string $lastFour = '4242',
        ?string $expMonth = '12',
        ?string $expYear = '2028',
        bool $makeDefault = false
    ): CustomerPaymentMethod {
        if ($makeDefault) {
            CustomerPaymentMethod::where('user_id', $userId)->update(['is_default' => false]);
        }

        return CustomerPaymentMethod::create([
            'user_id'              => $userId,
            'gateway'              => $gateway,
            'payment_method_token' => $token,
            'card_brand'           => strtolower($brand ?? 'card'),
            'card_last_four'       => $lastFour,
            'card_exp_month'       => $expMonth,
            'card_exp_year'        => $expYear,
            'is_default'           => $makeDefault,
        ]);
    }

    /**
     * Retrieve user's vaulted methods.
     */
    public function getUserMethods(int $userId): Collection
    {
        return CustomerPaymentMethod::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();
    }

    /**
     * Remove a vaulted method.
     */
    public function deleteMethod(int $userId, int $methodId): bool
    {
        $method = CustomerPaymentMethod::where('user_id', $userId)->where('id', $methodId)->first();
        if ($method) {
            return $method->delete();
        }

        return false;
    }
}
