<?php

use App\Http\Controllers\CatalogItemController;
use App\Http\Controllers\InventoryUnitController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:sales.view')->group(function () {
        Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/export', [SaleController::class, 'export'])->name('sales.export');
    });

    Route::middleware('permission:sales.create')->group(function () {
        Route::get('sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('sales', [SaleController::class, 'store'])->name('sales.store');
        Route::get('catalog-items/search', [CatalogItemController::class, 'search'])->name('catalog-items.search');
        Route::get('inventory-units/search', [InventoryUnitController::class, 'search'])->name('inventory-units.search');
    });

    Route::middleware('permission:sales.view')->group(function () {
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    });
});
