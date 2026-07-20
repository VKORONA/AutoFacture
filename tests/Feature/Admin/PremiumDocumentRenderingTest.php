<?php

use Crater\Models\Estimate;
use Crater\Models\EstimateItem;
use Crater\Models\Invoice;
use Crater\Models\InvoiceItem;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->user = User::findOrFail(1);
    $this->company = $this->user->companies()->firstOrFail();
    $this->withHeaders(['company' => $this->company->id]);
    Sanctum::actingAs($this->user, ['*']);
});

it('persists premium invoice fields and renders the approved PDF matrix', function () {
    $payload = Invoice::factory()->raw([
        'client_request_id' => (string) Str::uuid(),
        'invoice_number' => 'CLIENT-PREMIUM',
        'template_name' => 'invoice1',
        'project_name' => 'Résidence Les Alizés',
        'project_address' => '8 rue des Mouettes, 17000 La Rochelle',
        'purchase_order_number' => 'BC-2026-019',
        'project_contact' => 'M. Julien Martin',
        'payment_terms_label' => '30 jours fin de mois',
        'show_sepa_qr' => false,
        'items' => [InvoiceItem::factory()->raw()],
        'taxes' => [Tax::factory()->raw()],
    ]);

    $response = postJson('/api/v1/invoices', $payload)
        ->assertOk()
        ->assertJsonPath('data.project_name', 'Résidence Les Alizés')
        ->assertJsonPath('data.purchase_order_number', 'BC-2026-019')
        ->assertJsonPath('data.show_sepa_qr', false);

    $invoice = Invoice::with([
        'company.address',
        'company.owner',
        'customer.billingAddress',
        'customer.shippingAddress',
        'customer.currency',
        'items.taxes',
        'taxes',
        'fields.customField',
    ])->findOrFail($response->json('data.id'));

    $pdf = $invoice->getPDFData()->output();

    expect($pdf)->toStartWith('%PDF')
        ->and(strlen($pdf))->toBeGreaterThan(1000);
});

it('persists premium estimate fields and renders the approved PDF matrix', function () {
    $payload = Estimate::factory()->raw([
        'estimate_number' => 'DEV-PREMIUM-001',
        'template_name' => 'estimate1',
        'project_name' => 'Mission de contrôle technique',
        'project_address' => '12 rue des Artisans, 75011 Paris',
        'purchase_order_number' => 'BC-DEVIS-001',
        'project_contact' => 'Mme Dupont',
        'payment_terms_label' => 'Acompte de 30 % à la commande',
        'show_sepa_qr' => false,
        'items' => [EstimateItem::factory()->raw()],
        'taxes' => [Tax::factory()->raw()],
    ]);

    $response = postJson('/api/v1/estimates', $payload)
        ->assertCreated()
        ->assertJsonPath('data.project_name', 'Mission de contrôle technique')
        ->assertJsonPath('data.project_contact', 'Mme Dupont')
        ->assertJsonPath('data.show_sepa_qr', false);

    $estimate = Estimate::with([
        'company.address',
        'company.owner',
        'customer.billingAddress',
        'customer.shippingAddress',
        'customer.currency',
        'items.taxes',
        'taxes',
        'fields.customField',
    ])->findOrFail($response->json('data.id'));

    $pdf = $estimate->getPDFData()->output();

    expect($pdf)->toStartWith('%PDF')
        ->and(strlen($pdf))->toBeGreaterThan(1000);
});
