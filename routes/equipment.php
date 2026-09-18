<?php

use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentTransferController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:equipment.view')->group(function () {
        Route::get('equipment', [EquipmentController::class, 'index'])->name('equipment.index');
        Route::get('clients/{client}/equipment', [EquipmentController::class, 'forClient'])->name('clients.equipment.index');
    });

    Route::middleware('permission:equipment.create')->group(function () {
        Route::get('equipment/create', [EquipmentController::class, 'create'])->name('equipment.create');
        Route::post('equipment', [EquipmentController::class, 'store'])->name('equipment.store');
    });

    Route::middleware('permission:equipment.update')->group(function () {
        Route::get('equipment/{equipment}/edit', [EquipmentController::class, 'edit'])->name('equipment.edit');
        Route::put('equipment/{equipment}', [EquipmentController::class, 'update'])->name('equipment.update');
    });

    Route::middleware('permission:equipment.transfer')->group(function () {
        Route::post('equipment/{equipment}/transfer', [EquipmentTransferController::class, 'store'])->name('equipment.transfer');
    });

    Route::middleware('permission:equipment.view')->group(function () {
        Route::get('equipment/{equipment}', [EquipmentController::class, 'show'])->name('equipment.show');
    });
});
