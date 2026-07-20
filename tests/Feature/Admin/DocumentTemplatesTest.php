<?php

use Crater\Models\CompanySetting;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->user = User::findOrFail(1);
    $this->company = $this->user->companies()->firstOrFail();

    $this->withHeaders(['company' => $this->company->id]);
    Sanctum::actingAs($this->user, ['*']);
});

it('places document templates inside billing settings', function () {
    $menuItem = collect(config('autofacture.additional_setting_menu'))
        ->firstWhere('name', 'Document Templates');

    expect($menuItem)->not->toBeNull()
        ->and($menuItem['group'])->toBe('Facturation & Devis')
        ->and($menuItem['link'])->toBe('/admin/settings/document-templates')
        ->and(config('autofacture.settings_features.document_templates'))->toBeTrue();
});

it('loads five real invoice and estimate templates', function () {
    getJson('/api/v1/invoices/templates')
        ->assertOk()
        ->assertJsonCount(5, 'invoiceTemplates')
        ->assertJsonPath('invoiceTemplates.0.name', 'invoice1')
        ->assertJsonPath('invoiceTemplates.0.label', 'Premium AutoFacture')
        ->assertJsonPath('invoiceTemplates.3.name', 'nuit')
        ->assertJsonPath('invoiceTemplates.3.label', 'Standard universel')
        ->assertJsonPath('invoiceTemplates.3.theme', 'standard')
        ->assertJsonPath('invoiceTemplates.4.name', 'franchise-tva');

    getJson('/api/v1/estimates/templates')
        ->assertOk()
        ->assertJsonCount(5, 'estimateTemplates')
        ->assertJsonPath('estimateTemplates.0.name', 'estimate1')
        ->assertJsonPath('estimateTemplates.0.label', 'Premium AutoFacture')
        ->assertJsonPath('estimateTemplates.3.name', 'nuit')
        ->assertJsonPath('estimateTemplates.3.label', 'Standard universel')
        ->assertJsonPath('estimateTemplates.3.theme', 'standard')
        ->assertJsonPath('estimateTemplates.4.name', 'franchise-tva');
});

it('persists invoice estimate and credit note defaults', function () {
    putJson('/api/v1/me/settings', [
        'settings' => [
            'default_invoice_template' => 'nuit',
            'default_estimate_template' => 'franchise-tva',
        ],
    ])->assertOk();

    postJson('/api/v1/company/settings', [
        'settings' => [
            'default_credit_note_template' => 'minimal',
        ],
    ])->assertOk();

    $settings = $this->user->fresh()->getAllSettings();

    expect($settings->get('default_invoice_template'))->toBe('nuit')
        ->and($settings->get('default_estimate_template'))->toBe('franchise-tva')
        ->and(CompanySetting::getSetting(
            'default_credit_note_template',
            $this->company->id,
        ))->toBe('minimal');
});

it('ships the standard and VAT franchise PDF views', function () {
    expect(view()->exists('app.pdf.invoice.nuit'))->toBeTrue()
        ->and(view()->exists('app.pdf.invoice.franchise-tva'))->toBeTrue()
        ->and(view()->exists('app.pdf.estimate.nuit'))->toBeTrue()
        ->and(view()->exists('app.pdf.estimate.franchise-tva'))->toBeTrue();
});
