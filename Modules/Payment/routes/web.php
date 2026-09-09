<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\Admin\PaymentTransactionController;
use Modules\Payment\Http\Controllers\Customer\CustomerPaymentMethodController;
use Modules\Payment\Http\Controllers\Store\PaymentController;

/*
|--------------------------------------------------------------------------
| Customer Payment Methods (Vault) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('account/payment-methods')
    ->middleware(['web', 'auth', 'tenant.context'])
    ->name('account.payment_methods.')
    ->group(function () {
        Route::get('/', [CustomerPaymentMethodController::class, 'index'])->name('index');
        Route::post('/', [CustomerPaymentMethodController::class, 'store'])->name('store');
        Route::post('/{id}/default', [CustomerPaymentMethodController::class, 'setDefault'])->name('default');
        Route::delete('/{id}', [CustomerPaymentMethodController::class, 'destroy'])->name('destroy');
    });

/*
|--------------------------------------------------------------------------
| Storefront Payment Routes
|--------------------------------------------------------------------------
*/
Route::prefix('store/payment')->middleware(['web', 'tenant.context'])->name('store.payment.')->group(function () {
    Route::get('/process/{orderNumber}', [PaymentController::class, 'process'])->name('process');
    Route::get('/callback/{gateway}/{orderNumber}', [PaymentController::class, 'callback'])->name('callback');
});

/*
|--------------------------------------------------------------------------
| Sneat Admin Payment Transaction Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin/payments')->middleware(['web', 'auth', 'tenant.context'])->name('admin.payments.')->group(function () {
    Route::get('/', [PaymentTransactionController::class, 'index'])->name('index');
    Route::post('/{id}/refund', [PaymentTransactionController::class, 'refund'])->name('refund');
});
