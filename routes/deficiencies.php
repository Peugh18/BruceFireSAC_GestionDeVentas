<?php

use App\Http\Controllers\DeficiencyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:deficiencies.view')->group(function () {
        Route::get('deficiencies', [DeficiencyController::class, 'index'])->name('deficiencies.index');
    });

    Route::middleware('permission:deficiencies.create')->group(function () {
        Route::post('deficiencies', [DeficiencyController::class, 'store'])->name('deficiencies.store');
    });

    Route::patch('deficiencies/{deficiency}/status', [DeficiencyController::class, 'updateStatus'])->name('deficiencies.status');
});
