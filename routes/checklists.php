<?php

use App\Http\Controllers\ServiceOrderChecklistController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:checklists.view')->group(function () {
        Route::get('service-orders/{serviceOrder}/equipment/{equipment}/checklist', [ServiceOrderChecklistController::class, 'show'])->name('checklists.show');
    });

    Route::middleware('permission:checklists.fill')->group(function () {
        Route::post('service-orders/{serviceOrder}/equipment/{equipment}/checklist', [ServiceOrderChecklistController::class, 'store'])->name('checklists.store');
    });
});
