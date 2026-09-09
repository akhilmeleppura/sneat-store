<?php

use Illuminate\Support\Facades\Route;
use Modules\Context\Http\Controllers\Admin\AdminContextHubController;
use Modules\Context\Http\Controllers\Admin\AdminCurrencyController;
use Modules\Context\Http\Controllers\Admin\AdminTenantSettingController;
use Modules\Context\Http\Controllers\ContextSwitcherController;
use Modules\Context\Http\Controllers\CurrencySwitcherController;
use Modules\Context\Http\Controllers\StoreBranchController;

// Storefront & Public Currency Switcher Routes
Route::post('/currency/switch', [CurrencySwitcherController::class, 'switchCurrency'])->name('currency.switch');
Route::get('/currency/{code}', [CurrencySwitcherController::class, 'switchByGet'])->name('currency.switch.get');
Route::get('/api/currencies/active', [CurrencySwitcherController::class, 'active'])->name('currency.api.active');

// Multi-Tenant & Multi-Store Context Switcher Routes
Route::get('/context/switch-tenant/{id}', [ContextSwitcherController::class, 'switchTenant'])->name('context.switch.tenant');
Route::get('/context/switch-store/{id}', [ContextSwitcherController::class, 'switchStore'])->name('context.switch.store');
Route::get('/context/switch-branch/{id}', [ContextSwitcherController::class, 'switchBranch'])->name('context.switch.branch');

// Storefront Branch Switcher & Interactive Store Locator Map
Route::get('/branch/switch/{id}', [StoreBranchController::class, 'switchBranch'])->name('branch.switch');
Route::get('/store/locations', [StoreBranchController::class, 'locations'])->name('store.locations');
Route::get('/api/branches', [StoreBranchController::class, 'apiBranches'])->name('api.branches');

// Sneat Admin All-in-One Multi-Tenancy & Store Hierarchy Hub
Route::prefix('admin/context')
    ->name('admin.context.')
    ->middleware(['auth', 'tenant.context'])
    ->group(function () {
        Route::get('/', [AdminContextHubController::class, 'index'])->name('index');
        Route::post('/switch', [AdminContextHubController::class, 'switchContext'])->name('switch');

        // Tenant CRUD
        Route::post('/tenants', [AdminContextHubController::class, 'storeTenant'])->name('tenants.store');
        Route::put('/tenants/{id}', [AdminContextHubController::class, 'updateTenant'])->name('tenants.update');
        Route::delete('/tenants/{id}', [AdminContextHubController::class, 'deleteTenant'])->name('tenants.delete');

        // Store CRUD
        Route::post('/stores', [AdminContextHubController::class, 'storeStore'])->name('stores.store');
        Route::put('/stores/{id}', [AdminContextHubController::class, 'updateStore'])->name('stores.update');
        Route::delete('/stores/{id}', [AdminContextHubController::class, 'deleteStore'])->name('stores.delete');

        // Branch CRUD
        Route::post('/branches', [AdminContextHubController::class, 'storeBranch'])->name('branches.store');
        Route::put('/branches/{id}', [AdminContextHubController::class, 'updateBranch'])->name('branches.update');
        Route::delete('/branches/{id}', [AdminContextHubController::class, 'deleteBranch'])->name('branches.delete');
    });

// Sneat Admin Currency Management Routes
Route::prefix('admin/currencies')
    ->name('admin.currencies.')
    ->middleware(['auth', 'tenant.context'])
    ->group(function () {
        Route::get('/', [AdminCurrencyController::class, 'index'])->name('index');
        Route::post('/', [AdminCurrencyController::class, 'store'])->name('store');
        Route::post('/{id}/rate', [AdminCurrencyController::class, 'updateRate'])->name('rate');
        Route::post('/{id}/toggle', [AdminCurrencyController::class, 'toggleStatus'])->name('toggle');
        Route::post('/{id}/default', [AdminCurrencyController::class, 'setDefault'])->name('default');
    });

// Sneat Admin Tenant & Store Settings Routes
Route::prefix('admin/settings/tenant')
    ->middleware(['auth', 'tenant.context'])
    ->group(function () {
        Route::get('/', [AdminTenantSettingController::class, 'index'])->name('admin.tenant.settings');
        Route::post('/', [AdminTenantSettingController::class, 'update'])->name('admin.tenant.settings.update');
    });

