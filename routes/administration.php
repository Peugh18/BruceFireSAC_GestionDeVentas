<?php

use App\Http\Controllers\Administration\RoleController;
use App\Http\Controllers\Administration\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:users.manage')->group(function () {
        Route::get('administration/users', [UserController::class, 'index'])->name('administration.users.index');
        Route::post('administration/users', [UserController::class, 'store'])->name('administration.users.store');
        Route::put('administration/users/{user}', [UserController::class, 'update'])->name('administration.users.update');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('administration/roles', [RoleController::class, 'index'])->name('administration.roles.index');
        Route::get('administration/roles/{role}', [RoleController::class, 'show'])->name('administration.roles.show');
        Route::put('administration/roles/{role}', [RoleController::class, 'updatePermissions'])->name('administration.roles.update');
    });
});
