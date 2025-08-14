<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\App\Http\Controllers\BillingController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Route::resource('billings', BillingController::class)->names('billing');


    Route::prefix('/accounting/billings')->group(function() {
        Route::get('/', [BillingController::class, 'index'])->name('accounting.billings.index');
        Route::get('/invoices/add', [BillingController::class, 'create'])->name('accounting.billings.create');
        Route::post('/', [BillingController::class, 'store'])->name('billings.store');
        Route::get('/{id}', [BillingController::class, 'show'])->name('billings.show');
        Route::get('/{id}/edit', [BillingController::class, 'edit'])->name('billings.edit');
        Route::put('/{id}', [BillingController::class, 'update'])->name('billings.update');
        Route::delete('/{id}', [BillingController::class, 'destroy'])->name('billings.destroy');
    });
});