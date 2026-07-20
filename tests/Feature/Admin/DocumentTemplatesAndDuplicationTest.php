<?php

use Crater\Models\Estimate;
use Crater\Models\EstimateItem;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
    $this->user = User::findOrFail(1);
    $this->company = $this->user->companies()->firstOrFail();
    $this->withHeaders(['company' => $this->company->id]);
    Sanctum::actingAs($this->user, ['*']);
});

it('returns five real invoice and estimate previews from public assets', function () {
    $invoiceResponse = getJson('/api/v1/invoices/templates')->assertOk()->assertJsonCount(5, 'invoiceTemplates');
    $estimateResponse = getJson('/api/v1/estimates/templates')->assertOk()->assertJsonCount(5, 'estimateTemplates');

    foreach (array_merge($invoiceResponse->json('invoiceTemplates'), $estimateResponse->json('estimateTemplates')) as $template) {
        $relativePath = ltrim((string) parse_url($template['path'], PHP_URL_PATH), '/');
        expect($template['path'])->toContain('/img/document-templates/template-')
            ->and(public_path($relativePath))->toBeFile();
    }
});

it('maps every selectable design to a real AutoFacture PDF renderer', function () {
    $invoiceThemes = [
        'invoice1' => 'premium', 'invoice2' => 'classic', 'invoice3' => 'minimal',
        'nuit' => 'standard', 'franchise-tva' => 'franchise',
    ];
    $estimateThemes = [
        'estimate1' => 'premium', 'estimate2' => 'classic', 'estimate3' => 'minimal',
        'nuit' => 'standard', 'franchise-tva' => 'franchise',
    ];

    foreach ($invoiceThemes as $template => $theme) {
        $view = file_get_contents(resource_path("views/app/pdf/invoice/{$template}.blade.php"));
        $renderer = $template === 'invoice1'
            ? 'app.pdf.shared.autofacture-premium-invoice'
            : 'app.pdf.shared.autofacture-invoice';

        expect($view)->toContain("autofactureTheme = '{$theme}'")->toContain($renderer);
    }

    foreach ($estimateThemes as $template => $theme) {
        $view = file_get_contents(resource_path("views/app/pdf/estimate/{$template}.blade.php"));
        $renderer = $template === 'estimate1'
            ? 'app.pdf.shared.autofacture-premium-estimate'
            : 'app.pdf.shared.autofacture-estimate';

        expect($view)->toContain("autofactureTheme = '{$theme}'")->toContain($renderer);
    }
});

it('ships the approved premium matrix with SEPA, VAT and legal sections', function () {
    $invoiceView = file_get_contents(resource_path('views/app/pdf/shared/autofacture-premium-invoice.blade.php'));
    $estimateView = file_get_contents(resource_path('views/app/pdf/shared/autofacture-premium-estimate.blade.php'));
    $invoiceEntry = file_get_contents(resource_path('views/app/pdf/invoice/invoice1.blade.php'));
    $estimateEntry = file_get_contents(resource_path('views/app/pdf/estimate/estimate1.blade.php'));

    expect($invoiceView)
        ->toContain('SepaQrCodeService')
        ->toContain('QR code de virement')
        ->toContain('Net à payer')
        ->toContain('TVA non applicable, art. 293 B du CGI')
        ->toContain('Indemnité forfaitaire de 40 €')
        ->toContain('Matrice Premium AutoFacture verrouillée')
        ->and($estimateView)
        ->toContain('SepaQrCodeService')
        ->toContain('QR coordonnées bancaires')
        ->toContain('Acceptation du client')
        ->toContain('TVA non applicable, art. 293 B du CGI')
        ->toContain('Matrice Premium AutoFacture verrouillée')
        ->and($invoiceEntry)
        ->toContain('project_name')
        ->toContain('purchase_order_number')
        ->toContain('payment_terms')
        ->and($estimateEntry)
        ->toContain('project_address')
        ->toContain('project_contact');
});

it('duplicates an estimate as a new editable draft without copying attachments', function () {
    $source = Estimate::factory()
        ->has(EstimateItem::factory()->count(2), 'items')
        ->has(Tax::factory()->count(1), 'taxes')
        ->create([
            'company_id' => $this->company->id,
            'status' => Estimate::STATUS_ACCEPTED,
            'reference_number' => 'CHANTIER-DEMO',
            'project_name' => 'Résidence Les Alizés',
            'project_address' => '8 rue des Mouettes, La Rochelle',
            'purchase_order_number' => 'BC-2026-019',
            'project_contact' => 'M. Julien Martin',
            'payment_terms_label' => '30 jours fin de mois',
            'show_sepa_qr' => true,
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
        ->assertJsonPath('data.project_name', 'Résidence Les Alizés')
        ->assertJsonPath('data.purchase_order_number', 'BC-2026-019')
        ->assertJsonPath('data.notes', 'Contenu réutilisable');

    $duplicate = Estimate::with(['items', 'taxes'])->findOrFail($response->json('data.id'));
    expect($duplicate->id)->not->toBe($source->id)
        ->and($duplicate->estimate_number)->not->toBe($source->estimate_number)
        ->and($duplicate->items)->toHaveCount(2)
        ->and($duplicate->taxes)->toHaveCount(1)
        ->and((bool) $duplicate->include_photo_annex)->toBeFalse()
        ->and($duplicate->annex_title)->toBe('Annexe source')
        ->and($duplicate->annex_notes)->toBe('Précisions techniques')
        ->and($duplicate->project_address)->toBe('8 rue des Mouettes, La Rochelle')
        ->and($duplicate->project_contact)->toBe('M. Julien Martin')
        ->and($duplicate->payment_terms_label)->toBe('30 jours fin de mois')
        ->and((bool) $duplicate->show_sepa_qr)->toBeTrue();
});

it('keeps duplicate actions explicit and opens an editable customer form', function () {
    $invoiceDropdown = file_get_contents(resource_path('scripts/admin/components/dropdowns/InvoiceIndexDropdown.vue'));
    $estimateDropdown = file_get_contents(resource_path('scripts/admin/components/dropdowns/EstimateIndexDropdown.vue'));
    $invoiceFields = file_get_contents(resource_path('scripts/admin/views/invoices/create/InvoiceCreateBasicFields.vue'));
    $estimateFields = file_get_contents(resource_path('scripts/admin/views/estimates/create/EstimateCreateBasicFields.vue'));

    expect($invoiceDropdown)->toContain('Dupliquer')->toContain('duplicated_from')->toContain('/edit')
        ->and($estimateDropdown)->toContain('Dupliquer')->toContain('duplicated_from')->toContain('/edit')
        ->and($invoiceFields)->toContain('BaseCustomerSelectPopup')->toContain('DocumentPresentationFields')
        ->and($estimateFields)->toContain('BaseCustomerSelectPopup')->toContain('DocumentPresentationFields');
});
