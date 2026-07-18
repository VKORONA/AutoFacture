<?php

use Crater\Http\Controllers\V1\Admin\CreditNote\CreditNotesController;
use Crater\Http\Controllers\V1\Admin\Invoice\FinalizeInvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'company', 'bouncer'])
    ->group(function (): void {
        Route::post('/invoices/{invoice}/finalize', FinalizeInvoiceController::class)
            ->name('invoices.finalize');

        Route::post('/invoices/{invoice}/credit-notes', [CreditNotesController::class, 'store'])
            ->name('invoices.credit-notes.store');

        Route::get('/credit-notes', [CreditNotesController::class, 'index'])
            ->name('credit-notes.index');

        Route::get('/credit-notes/{creditNote}', [CreditNotesController::class, 'show'])
            ->name('credit-notes.show');
    });
