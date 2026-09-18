<?php

use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\InventoryReceptionController;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

Route::middleware(['auth', PermissionMiddleware::using('inventory.view')])->prefix('inventory')->name('inventory.')->group(function (): void {
    Route::get('/', [InventoryController::class, 'index'])->name('index');
    Route::get('/receive', [InventoryReceptionController::class, 'create'])->middleware(PermissionMiddleware::using('inventory.receive'))->name('receive');
    Route::post('/receive', [InventoryReceptionController::class, 'store'])->middleware(PermissionMiddleware::using('inventory.receive'))->name('receive.store');
    Route::get('/movements', [InventoryMovementController::class, 'index'])->name('movements');
    Route::get('/{stock}', [InventoryController::class, 'show'])->name('show');
    Route::patch('/{stock}', [InventoryController::class, 'update'])->middleware(PermissionMiddleware::using('inventory.adjust'))->name('update');
    Route::post('/{stock}/movements', [InventoryMovementController::class, 'store'])->middleware(PermissionMiddleware::using('inventory.adjust'))->name('movements.store');
});
