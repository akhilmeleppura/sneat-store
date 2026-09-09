<?php

use Illuminate\Support\Facades\Route;
use Modules\Rewards\Http\Controllers\CustomerRewardController;
use Modules\Rewards\Http\Controllers\RewardController;

// Admin Loyalty & Rewards Management Routes
Route::prefix('admin/rewards')->name('admin.rewards.')->middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/', [RewardController::class, 'index'])->name('index');
    Route::get('/create', [RewardController::class, 'create'])->name('create');
    Route::post('/', [RewardController::class, 'store'])->name('store');
    Route::get('/customers', [RewardController::class, 'customers'])->name('customers');
    Route::get('/{id}/edit', [RewardController::class, 'edit'])->name('edit');
    Route::put('/{id}', [RewardController::class, 'update'])->name('update');
    Route::post('/{id}/toggle', [RewardController::class, 'toggle'])->name('toggle');
    Route::delete('/{id}', [RewardController::class, 'destroy'])->name('destroy');
});

// Storefront Customer Rewards & Loyalty Endpoints
Route::prefix('store/rewards')->name('store.rewards.')->middleware(['web', 'tenant.context'])->group(function () {
    Route::get('/balance', [CustomerRewardController::class, 'balance'])->name('balance');
    Route::get('/history', [CustomerRewardController::class, 'history'])->name('history');
    Route::post('/estimate', [CustomerRewardController::class, 'estimate'])->name('estimate');
});
