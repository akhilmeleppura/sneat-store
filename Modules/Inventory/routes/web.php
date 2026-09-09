<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryController;

Route::prefix('inventory')->name('inventory.')->middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
    Route::get('/transactions', [InventoryController::class, 'transactions'])->name('transactions');
});
