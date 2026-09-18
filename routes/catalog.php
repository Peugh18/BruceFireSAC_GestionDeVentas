<?php

use App\Http\Controllers\CatalogItemController;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

Route::middleware(['auth', PermissionMiddleware::using('catalog.view')])->group(function (): void {
    Route::resource('catalog', CatalogItemController::class)
        ->parameters(['catalog' => 'catalogItem'])
        ->only(['index', 'create', 'store', 'edit', 'update']);
});
