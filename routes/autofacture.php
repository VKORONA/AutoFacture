<?php

use Crater\Http\Controllers\V1\Admin\Accounting\AccountingController;
use Crater\Http\Controllers\V1\Admin\CreditNote\CreditNotesController;
use Crater\Http\Controllers\V1\Admin\ElectronicInvoicing\ElectronicInvoiceConnectionController;
use Crater\Http\Controllers\V1\Admin\Estimate\CloneEstimateController;
use Crater\Http\Controllers\V1\Admin\Estimate\EstimateAssetController;
use Crater\Http\Controllers\V1\Admin\Invoice\FinalizeInvoiceController;
use Crater\Http\Controllers\V1\Admin\MicroEntrepreneur\MicroEntrepreneurController;
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

        Route::post('/estimates/{estimate}/clone', CloneEstimateController::class)
            ->name('estimates.clone');

        Route::prefix('estimate-assets')->group(function (): void {
            Route::post('/photos', [EstimateAssetController::class, 'storePhoto'])
                ->name('estimate-assets.photos.store');
            Route::delete('/photos/{photo}', [EstimateAssetController::class, 'destroyPhoto'])
                ->name('estimate-assets.photos.destroy');
            Route::post('/attachments', [EstimateAssetController::class, 'storeAttachment'])
                ->name('estimate-assets.attachments.store');
            Route::delete('/attachments/{attachment}', [EstimateAssetController::class, 'destroyAttachment'])
                ->name('estimate-assets.attachments.destroy');
        });

        Route::prefix('accounting')->group(function (): void {
            Route::get('/', [AccountingController::class, 'show'])
                ->name('accounting.show');
            Route::put('/settings', [AccountingController::class, 'updateSettings'])
                ->name('accounting.settings.update');
            Route::post('/exports', [AccountingController::class, 'generate'])
                ->name('accounting.exports.generate');
            Route::get('/exports/{batch}/download', [AccountingController::class, 'download'])
                ->name('accounting.exports.download');
        });

        Route::prefix('micro-entrepreneur')->group(function (): void {
            Route::get('/', [MicroEntrepreneurController::class, 'show'])
                ->name('micro-entrepreneur.show');
            Route::put('/settings', [MicroEntrepreneurController::class, 'updateSettings'])
                ->name('micro-entrepreneur.settings.update');
            Route::post('/adjustments', [MicroEntrepreneurController::class, 'storeAdjustment'])
                ->name('micro-entrepreneur.adjustments.store');
            Route::delete('/adjustments/{adjustment}', [MicroEntrepreneurController::class, 'destroyAdjustment'])
                ->name('micro-entrepreneur.adjustments.destroy');
            Route::get('/export', [MicroEntrepreneurController::class, 'export'])
                ->name('micro-entrepreneur.export');
        });

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

Route::prefix('v1/estimate-assets')
    ->middleware(['auth:sanctum'])
    ->group(function (): void {
        Route::get('/photos/{photo}/{variant?}', [EstimateAssetController::class, 'showPhoto'])
            ->whereIn('variant', ['thumbnail', 'preview', 'image'])
            ->name('estimate-assets.photos.show');
        Route::get('/attachments/{attachment}/download', [EstimateAssetController::class, 'downloadAttachment'])
            ->name('estimate-assets.attachments.download');
    });
