<?php

use App\Http\Controllers\QuoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:quotes.view')->group(function () {
        Route::get('quotes', [QuoteController::class, 'index'])->name('quotes.index');
    });

    Route::middleware('permission:quotes.create')->group(function () {
        Route::get('quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
        Route::post('quotes', [QuoteController::class, 'store'])->name('quotes.store');
        Route::post('quotes/{quote}/duplicate', [QuoteController::class, 'duplicate'])->name('quotes.duplicate');
    });

    Route::middleware('permission:quotes.update')->group(function () {
        Route::get('quotes/{quote}/edit', [QuoteController::class, 'edit'])->name('quotes.edit');
        Route::put('quotes/{quote}', [QuoteController::class, 'update'])->name('quotes.update');
        Route::patch('quotes/{quote}/status', [QuoteController::class, 'changeStatus'])->name('quotes.status');
    });

    Route::middleware('permission:quotes.convert')->group(function () {
        Route::post('quotes/{quote}/convert', [QuoteController::class, 'convert'])->name('quotes.convert');
    });

    Route::middleware('permission:quotes.view')->group(function () {
        Route::get('quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');
    });
});
