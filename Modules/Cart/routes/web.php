<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\Admin\AbandonedCartAdminController;
use Modules\Cart\Http\Controllers\CartController;
use Modules\Cart\Http\Controllers\Store\CartRecoveryController;

// Storefront Cart Operations
Route::prefix('store/cart')->name('store.cart.')->middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::post('/update', [CartController::class, 'update'])->name('update');
    Route::delete('/item/{id}', [CartController::class, 'remove'])->name('remove');
    Route::post('/coupon', [CartController::class, 'applyCoupon'])->name('coupon');
    Route::delete('/coupon', [CartController::class, 'removeCoupon'])->name('coupon.remove');
});

// One-Click Abandoned Cart Recovery Link
Route::middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/cart/recover/{token}', [CartRecoveryController::class, 'recover'])->name('cart.recover');
});

// Sneat Admin Abandoned Cart Recovery Management
Route::prefix('admin/abandoned-carts')->name('admin.abandoned_carts.')->middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/', [AbandonedCartAdminController::class, 'index'])->name('index');
    Route::post('/scan', [AbandonedCartAdminController::class, 'triggerScan'])->name('scan');
    Route::post('/{id}/remind', [AbandonedCartAdminController::class, 'sendReminder'])->name('remind');
});
