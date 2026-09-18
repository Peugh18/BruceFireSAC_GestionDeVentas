<?php

use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:sales.view')->group(function () {
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
    });

    Route::middleware('permission:sales.create')->group(function () {
        Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
    });

    Route::middleware('permission:sales.view')->group(function () {
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    });
});
