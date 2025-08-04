<?php

use Illuminate\Support\Facades\Route;
use Modules\SampleModule\Http\Controllers\SampleModuleController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('samplemodules', SampleModuleController::class)->names('samplemodule');
});
 Route::get('/samplemodule', [SampleModuleController::class, 'index'])->name('index');
