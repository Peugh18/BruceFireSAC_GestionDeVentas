<?php

use App\Http\Controllers\CollectionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'permission:collections.view'])->scopeBindings()->group(function (): void {
    Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::get('collections/{sale}', [CollectionController::class, 'show'])->name('collections.show');
    Route::post('collections/{sale}/installments/{installment}/payments', [CollectionController::class, 'storePayment'])
        ->middleware('permission:collections.register_payment')->name('collections.payments.store');
});
