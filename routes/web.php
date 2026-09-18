<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/catalog.php';
require __DIR__.'/inventory.php';
require __DIR__.'/clients.php';
require __DIR__.'/equipment.php';
require __DIR__.'/service-orders.php';
require __DIR__.'/quotes.php';
require __DIR__.'/sales.php';
require __DIR__.'/collections.php';
require __DIR__.'/checklists.php';
require __DIR__.'/deficiencies.php';
require __DIR__.'/certificates.php';
require __DIR__.'/auth.php';
