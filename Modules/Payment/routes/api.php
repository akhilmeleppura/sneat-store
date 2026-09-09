<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\Webhook\PaymentWebhookController;

/*
|--------------------------------------------------------------------------
| Payment Webhook API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('webhooks')->group(function () {
    Route::post('/{gateway}', [PaymentWebhookController::class, 'handle'])->name('payment.webhook');
});
