<?php

use Illuminate\Support\Facades\Route;
use Modules\General\Http\Controllers\GeneralController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('generals', GeneralController::class)->names('general');
});
