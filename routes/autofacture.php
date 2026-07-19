<?php

use Crater\Http\Controllers\V1\Admin\CreditNote\CreditNotesController;
use Crater\Http\Controllers\V1\Admin\ElectronicInvoicing\ElectronicInvoiceConnectionController;
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

        Route::prefix('electronic-invoicing')->group(function (): void {
            Route::get('/connection', [ElectronicInvoiceConnectionController::class, 'show'])
                ->name('electronic-invoicing.connection.show');
            Route::post('/connection/progress', [ElectronicInvoiceConnectionController::class, 'updateProgress'])
                ->name('electronic-invoicing.connection.progress');
            Route::put('/connection/credentials', [ElectronicInvoiceConnectionController::class, 'storeCredentials'])
                ->name('electronic-invoicing.connection.credentials');
            Route::post('/connection/test', [ElectronicInvoiceConnectionController::class, 'test'])
                ->name('electronic-invoicing.connection.test');
            Route::delete('/connection', [ElectronicInvoiceConnectionController::class, 'disconnect'])
                ->name('electronic-invoicing.connection.disconnect');
        });
    });
