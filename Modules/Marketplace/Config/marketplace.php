<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Marketplace Commission Settings
    |--------------------------------------------------------------------------
    */
    'default_commission_rate' => env('MARKETPLACE_DEFAULT_COMMISSION', 10.00), // 10%
    'commission_type'         => 'percentage', // percentage or fixed

    /*
    |--------------------------------------------------------------------------
    | Vendor Payout Thresholds
    |--------------------------------------------------------------------------
    */
    'min_payout_amount'       => env('MARKETPLACE_MIN_PAYOUT', 50.00),
    'auto_approve_vendors'    => env('MARKETPLACE_AUTO_APPROVE_VENDORS', false),

    /*
    |--------------------------------------------------------------------------
    | Supported Payout Methods
    |--------------------------------------------------------------------------
    */
    'payout_methods' => [
        'bank_transfer' => 'Direct Bank Wire',
        'paypal'        => 'PayPal Express Payout',
        'stripe'        => 'Stripe Connect',
    ],
];
