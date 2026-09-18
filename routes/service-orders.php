<?php

use App\Http\Controllers\ServiceOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:service_orders.view'])->group(function (): void {
    Route::resource('service-orders', ServiceOrderController::class)
        ->only(['create', 'store'])
        ->middleware('permission:service_orders.create');

    Route::resource('service-orders', ServiceOrderController::class)->only(['index', 'show']);

    Route::patch('service-orders/{service_order}/status', [ServiceOrderController::class, 'changeStatus'])
        ->middleware('permission:service_orders.receive|service_orders.execute|service_orders.close|service_orders.create')
        ->name('service-orders.status');

    Route::resource('service-orders', ServiceOrderController::class)
        ->only(['update'])
        ->middleware('permission:service_orders.assign');
});
