<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketplace\Http\Controllers\Admin\AdminVendorController;
use Modules\Marketplace\Http\Controllers\Store\VendorRegistrationController;
use Modules\Marketplace\Http\Controllers\Store\VendorStorefrontController;
use Modules\Marketplace\Http\Controllers\Vendor\VendorDashboardController;
use Modules\Marketplace\Http\Controllers\Vendor\VendorOrderController;
use Modules\Marketplace\Http\Controllers\Vendor\VendorPayoutController;
use Modules\Marketplace\Http\Controllers\Vendor\VendorProductController;

/*
|--------------------------------------------------------------------------
| Public Vendor Storefront & Seller Onboarding Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/vendors/{slug}', [VendorStorefrontController::class, 'show'])->name('storefront.vendor.show');
    Route::get('/vendor/register', [VendorRegistrationController::class, 'showRegistrationForm'])->name('storefront.vendor.register');
    Route::post('/vendor/register', [VendorRegistrationController::class, 'register'])->name('storefront.vendor.register.post');
});

/*
|--------------------------------------------------------------------------
| Dedicated Vendor Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('vendor')
    ->middleware(['web', 'auth', 'tenant.context', 'vendor.auth'])
    ->name('vendor.')
    ->group(function () {
        Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('dashboard');

        // Products
        Route::get('/products', [VendorProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [VendorProductController::class, 'create'])->name('products.create');
        Route::post('/products', [VendorProductController::class, 'store'])->name('products.store');

        // Orders
        Route::get('/orders', [VendorOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}', [VendorOrderController::class, 'show'])->name('orders.show');

        // Payouts & Earnings
        Route::get('/payouts', [VendorPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [VendorPayoutController::class, 'store'])->name('payouts.store');
    });

/*
|--------------------------------------------------------------------------
| Sneat Admin Marketplace Management Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin/marketplace')
    ->middleware(['web', 'auth', 'tenant.context'])
    ->name('admin.marketplace.')
    ->group(function () {
        Route::get('/vendors', [AdminVendorController::class, 'index'])->name('vendors.index');
        Route::post('/vendors/{id}/status', [AdminVendorController::class, 'updateStatus'])->name('vendors.status');
        Route::get('/payouts', [AdminVendorController::class, 'payouts'])->name('payouts.index');
        Route::post('/payouts/{id}/action', [AdminVendorController::class, 'handlePayoutAction'])->name('payouts.action');
    });
