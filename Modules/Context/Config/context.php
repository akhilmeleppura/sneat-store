<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Detection Modes
    |--------------------------------------------------------------------------
    | Priority order in which the ContextService will attempt to detect the tenant:
    | 'subdomain' -> e.g. acme.domain.com
    | 'header'    -> e.g. X-Tenant-Key / X-Tenant-Id
    | 'query'     -> e.g. ?tenant=acme
    | 'session'   -> session('current_tenant_id')
    | 'auth'      -> auth()->user()->tenant_id
    */
    'detection_order' => [
        'subdomain',
        'header',
        'query',
        'session',
        'auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    | The main domain for platform administration (not treated as a tenant).
    */
    'central_domain' => env('CENTRAL_DOMAIN', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | Tenancy Database Strategy
    |--------------------------------------------------------------------------
    | 'single' = Single database with tenant_id scoping (Recommended primary).
    | 'multi'  = Separate database per tenant (Dynamically reconfigured connection).
    | 'hybrid' = Single by default, but checks tenant model for custom connection.
    */
    'database_strategy' => env('TENANT_DB_STRATEGY', 'hybrid'),

    /*
    |--------------------------------------------------------------------------
    | Automatic Migration
    |--------------------------------------------------------------------------
    | When creating a dedicated database tenant, automatically run tenant migrations.
    */
    'auto_migrate' => env('TENANT_AUTO_MIGRATE', true),

    /*
    |--------------------------------------------------------------------------
    | Request Header Keys
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'tenant' => 'X-Tenant-Key',
        'store'  => 'X-Store-Key',
        'branch' => 'X-Branch-Key',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    */
    'models' => [
        'tenant'         => \Modules\Context\Models\Tenant::class,
        'store'          => \Modules\Context\Models\Store::class,
        'branch'         => \Modules\Context\Models\Branch::class,
        'tenant_setting' => \Modules\Context\Models\TenantSetting::class,
        'user'           => \App\Models\User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | RBAC Scoping Conventions
    |--------------------------------------------------------------------------
    | Mode can be 'tenant_prefix' (e.g. tenant.{tenant_id}.role_name)
    */
    'rbac' => [
        'prefix_style' => 'tenant_prefix', // 'tenant_prefix' or 'global'
        'prefix'       => 'tenant',
        'separator'    => '.',
    ],
];
