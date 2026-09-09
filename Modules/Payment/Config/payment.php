<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    | Supported: "stripe", "paypal", "mock", "offline"
    */
    'default' => env('PAYMENT_DEFAULT_GATEWAY', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Gateway Configurations
    |--------------------------------------------------------------------------
    */
    'gateways' => [
        'stripe' => [
            'name'           => 'Stripe',
            'publishable_key' => env('STRIPE_KEY', ''),
            'secret_key'      => env('STRIPE_SECRET', ''),
            'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET', ''),
            'currency'        => env('STRIPE_CURRENCY', 'USD'),
        ],

        'paypal' => [
            'name'           => 'PayPal',
            'client_id'      => env('PAYPAL_CLIENT_ID', ''),
            'secret'         => env('PAYPAL_SECRET', ''),
            'webhook_id'     => env('PAYPAL_WEBHOOK_ID', ''),
            'mode'           => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
            'currency'       => env('PAYPAL_CURRENCY', 'USD'),
        ],

        'offline' => [
            'name'           => 'Offline / Cash on Delivery',
            'methods'        => ['cod', 'bank_transfer'],
            'auto_settle'    => false,
        ],

        'mock' => [
            'name'           => 'Mock / Sandbox Simulator',
            'auto_approve'   => true,
            'currency'       => 'USD',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Financial Settlement Chart of Account Codes
    |--------------------------------------------------------------------------
    */
    'settlement' => [
        'clearing_account_identifier' => '1010',
        'clearing_account_name'       => 'Operating Cash & Gateway Clearing',
        'sales_revenue_identifier'    => '4000',
        'sales_revenue_name'          => 'E-Commerce Sales Revenue',
        'tax_payable_identifier'      => '2020',
        'tax_payable_name'            => 'Sales Tax & VAT Payable',
        'shipping_revenue_identifier' => '4010',
        'shipping_revenue_name'       => 'Shipping & Handling Revenue',
    ],
];
