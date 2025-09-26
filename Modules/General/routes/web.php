<?php

use Illuminate\Support\Facades\Route;
use Modules\General\App\Http\Controllers\GeneralController;
use Modules\General\App\Http\Controllers\DocumentTemplate\DocumentTemplateController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('generals', GeneralController::class)->names('general');


});
Route::prefix('general/document/templates')->group(function () {
    Route::get('/', [DocumentTemplateController::class, 'index'])->name('general.templates.index');
    Route::get('/create', [DocumentTemplateController::class, 'create'])->name('general.templates.create');
    Route::post('/store', [DocumentTemplateController::class, 'store'])->name('general.templates.store');
    Route::get('/{uuid}/edit', [DocumentTemplateController::class, 'edit'])->name('general.templates.edit');
    Route::put('/{uuid}', [DocumentTemplateController::class, 'update'])->name('general.templates.update');
    Route::delete('/{uuid}', [DocumentTemplateController::class, 'destroy'])->name('general.templates.destroy');
    Route::patch('/{uuid}/status', [DocumentTemplateController::class, 'toggleStatus'])->name('general.templates.toggle-status');
});