<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientSiteController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:clients.view')->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    });

    Route::middleware('permission:clients.create')->group(function () {
        Route::get('clients/create', [ClientController::class, 'create'])->name('clients.create');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
    });

    Route::middleware('permission:clients.update')->group(function () {
        Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');

        Route::post('clients/{client}/sites', [ClientSiteController::class, 'store'])->name('clients.sites.store');
        Route::put('clients/{client}/sites/{site}', [ClientSiteController::class, 'update'])->name('clients.sites.update');

        Route::post('clients/{client}/vehicles', [VehicleController::class, 'store'])->name('clients.vehicles.store');
        Route::put('clients/{client}/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('clients.vehicles.update');
    });

    Route::middleware('permission:clients.view')->group(function () {
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
    });
});
