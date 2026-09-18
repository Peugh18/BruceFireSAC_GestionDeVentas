<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('certificates/verify/{token}', [CertificateVerificationController::class, 'show'])->name('certificates.verify');

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:certificates.view')->group(function () {
        Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
        Route::get('certificates/{certificate}', [CertificateController::class, 'show'])->name('certificates.show');
    });

    Route::middleware('permission:certificates.issue')->group(function () {
        Route::post('certificates', [CertificateController::class, 'store'])->name('certificates.store');
    });

    Route::middleware('permission:certificates.manage')->group(function () {
        Route::patch('certificates/{certificate}/status', [CertificateController::class, 'updateStatus'])->name('certificates.status');
    });
});
