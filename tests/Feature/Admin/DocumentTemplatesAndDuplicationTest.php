<?php

use Crater\Models\Estimate;
use Crater\Models\EstimateItem;
use Crater\Models\Invoice;
use Crater\Models\InvoiceItem;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->user = User::findOrFail(1);
    $this->company = $this->user->companies()->firstOrFail();

    $this->withHeaders([
        'company' => $this->company->id,
    ]);

    Sanctum::actingAs($this->user, ['*']);
});

it('returns five real invoice and estimate previews from public assets', function () {
    $invoiceResponse = getJson('/api/v1/invoices/templates')
        ->assertOk()
        ->assertJsonCount(5, 'invoiceTemplates');

    $estimateResponse = getJson('/api/v1/estimates/templates')
        ->assertOk()
        ->assertJsonCount(5, 'estimateTemplates');

    foreach ($invoiceResponse->json('invoiceTemplates') as $template) {
        expect($template['path'])->toContain('/img/document-templates/template-')
            ->and(public_path(parse_url($template['path'], PHP_URL_PATH)))->toBeFile();
    }

    foreach ($estimateResponse->json('estimateTemplates') as $template) {
        expect($template['path'])->toContain('/img/document-templates/template-')
            ->and(public_path(parse_url($template['path'], PHP_URL_PATH)))->toBeFile();
    }
});

it('maps every selectable design to a real AutoFacture PDF renderer', function () {
    $invoiceThemes = [
        'invoice1' => 'premium',
        'invoice2' => 'classic',
        'invoice3' => 'minimal',
        'nuit' => 'standard',
        'franchise-tva' => 'franchise',
    ];

    $estimateThemes = $invoiceThemes;

    foreach ($invoiceThemes as $template => $theme) {
        $view = file_get_contents(resource_path("views/app/pdf/invoice/{$template}.blade.php"));
        expect($view)
            ->toContain("autofactureTheme = '{$theme}'")
            ->toContain("app.pdf.shared.autofacture-invoice");
    }

    foreach ($estimateThemes as $template => $theme) {
        $view = file_get_contents(resource_path("views/app/pdf/estimate/{$template}.blade.php"));
        expect($view)
            ->toContain("autofactureTheme = '{$theme}'")
            ->toContain("app.pdf.shared.autofacture-estimate");
    }
});

it('duplicates an estimate as a new editable draft without copying attachments', function () {
    $source = Estimate::factory()
        ->has(EstimateItem::factory()->count(2), 'items')
        ->has(Tax::factory()->count(1), 'taxes')
        ->create([
            'company_id' => $this->company->id,
            'status' => Estimate::STATUS_ACCEPTED,
            'reference_number' => 'CHANTIER-DEMO',
            'notes' => 'Contenu réutilisable',
            'annex_title' => 'Annexe source',
            'annex_notes' => 'Précisions techniques',
            'include_photo_annex' => true,
        ]);

    $response = postJson("/api/v1/estimates/{$source->id}/clone")
        ->assertCreated()
        ->assertJsonPath('meta.duplicated_from', $source->id)
        ->assertJsonPath('meta.attachments_copied', false)
        ->assertJsonPath('data.status', Estimate::STATUS_DRAFT)
        ->assertJsonPath('data.customer_id', $source->customer_id)
        ->assertJsonPath('data.reference_number', 'CHANTIER-DEMO')
        ->assertJsonPath('data.notes', 'Contenu réutilisable');

    $duplicate = Estimate::with(['items', 'taxes'])->findOrFail($response->json('data.id'));

    expect($duplicate->id)->not->toBe($source->id)
        ->and($duplicate->estimate_number)->not->toBe($source->estimate_number)
        ->and($duplicate->items)->toHaveCount(2)
        ->and($duplicate->taxes)->toHaveCount(1)
        ->and((bool) $duplicate->include_photo_annex)->toBeFalse()
        ->and($duplicate->annex_title)->toBe('Annexe source')
        ->and($duplicate->annex_notes)->toBe('Précisions techniques');
});

it('keeps duplicate actions explicit and opens an editable customer form', function () {
    $invoiceDropdown = file_get_contents(resource_path('scripts/admin/components/dropdowns/InvoiceIndexDropdown.vue'));
    $estimateDropdown = file_get_contents(resource_path('scripts/admin/components/dropdowns/EstimateIndexDropdown.vue'));

    expect($invoiceDropdown)
        ->toContain('Dupliquer')
        ->toContain('duplicated_from')
        ->toContain('/edit')
        ->and($estimateDropdown)
        ->toContain('Dupliquer')
        ->toContain('duplicated_from')
        ->toContain('/edit');
});
