<?php

use App\Http\Controllers\ShippingGuideController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:shipping_guides.view')->group(function () {
        Route::get('shipping', [ShippingGuideController::class, 'index'])->name('shipping.index');
    });

    Route::middleware('permission:shipping_guides.create')->group(function () {
        Route::get('shipping/create', [ShippingGuideController::class, 'create'])->name('shipping.create');
        Route::post('shipping', [ShippingGuideController::class, 'store'])->name('shipping.store');
    });
});
