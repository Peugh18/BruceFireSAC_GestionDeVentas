<?php

use App\Http\Controllers\ServiceOrderActaController;
use App\Http\Controllers\ServiceOrderPickupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function (): void {
    Route::resource('pickups', ServiceOrderPickupController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('pickups/{pickup}/custody', [ServiceOrderPickupController::class, 'updateCustody'])->name('pickups.custody.update');

    Route::get('service-orders/{service_order}/acta', [ServiceOrderActaController::class, 'show'])->name('service-orders.acta.show');
});
