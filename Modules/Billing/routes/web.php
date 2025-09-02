<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\App\Http\Controllers\BillingController;
use Modules\Billing\App\Http\Controllers\InvoiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Route::resource('billings', BillingController::class)->names('billing');


    Route::prefix('/accounting/billings')->group(function() {
        Route::get('/', [BillingController::class, 'index'])->name('accounting.billings.index');
        Route::get('/invoices/add', [BillingController::class, 'create'])->name('accounting.billings.create');
        Route::post('/store', [InvoiceController::class, 'store'])->name('billing.invoices.store');
        Route::get('/{id}', [BillingController::class, 'show'])->name('billings.show');
        Route::get('invoices/{id}/edit', [BillingController::class, 'edit'])->name('billings.edit');
        Route::put('invoices/{id}', [InvoiceController::class, 'update'])->name('billing.invoices.update');
        Route::delete('/{id}', [BillingController::class, 'destroy'])->name('billings.destroy');

    Route::get('/invoices/list', [InvoiceController::class, 'getInvoices'])->name('billing.invoices.index');
  Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('billing.invoices.destroy');
  Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('billing.invoices.show');
  Route::get('/invoices/{id}/download', [InvoiceController::class, 'download'])
    ->name('billing.invoices.download');
   Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])
    ->name('billing.invoices.print');

 Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])
    ->name('billing.invoices.edit');



    });

});