<?php

use App\Http\Controllers\ElectronicDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:billing.issue')->group(function () {
        Route::post('sales/{sale}/electronic-document', [ElectronicDocumentController::class, 'store'])->name('billing.issue');
    });

    Route::middleware('permission:billing.retry')->group(function () {
        Route::post('electronic-documents/{electronicDocument}/retry', [ElectronicDocumentController::class, 'retry'])->name('billing.retry');
    });
});
