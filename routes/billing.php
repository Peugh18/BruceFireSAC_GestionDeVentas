<?php

use App\Http\Controllers\CreditDebitNoteController;
use App\Http\Controllers\ElectronicDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('permission:billing.view')->group(function () {
        Route::get('electronic-documents', [ElectronicDocumentController::class, 'index'])->name('billing.index');
        Route::get('electronic-documents/{electronicDocument}/xml', [ElectronicDocumentController::class, 'downloadXml'])->name('billing.documents.xml');
        Route::get('electronic-documents/{electronicDocument}/cdr', [ElectronicDocumentController::class, 'downloadCdr'])->name('billing.documents.cdr');
    });

    Route::middleware('permission:billing.issue')->group(function () {
        Route::post('sales/{sale}/electronic-document', [ElectronicDocumentController::class, 'store'])->name('billing.issue');
    });

    Route::middleware('permission:billing.retry')->group(function () {
        Route::post('electronic-documents/{electronicDocument}/retry', [ElectronicDocumentController::class, 'retry'])->name('billing.retry');
    });

    Route::middleware('permission:billing.credit_note')->group(function () {
        Route::post('electronic-documents/{electronicDocument}/credit-debit-notes', [CreditDebitNoteController::class, 'store'])->name('billing.credit-debit-notes.store');
    });
});
