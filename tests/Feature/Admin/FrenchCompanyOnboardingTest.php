<?php

use Crater\Domain\FrenchInvoicing\FrenchCompanySetup;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\Invoice;
use Crater\Models\InvoiceItem;
use Crater\Models\Tax;
use Crater\Models\TaxType;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

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

function frenchCompanyPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'complete_setup' => true,
        'name' => 'Entreprise Française de Test',
        'legal_form' => 'SASU',
        'siren' => '732829320',
        'siret' => '73282932000074',
        'vat_regime' => 'standard',
        'vat_exempt' => false,
        'vat_number' => 'FR88732829320',
        'address' => [
            'country_id' => 1,
            'address_street_1' => '10 rue de la République',
            'address_street_2' => '',
            'city' => 'Toulouse',
            'zip' => '31000',
            'state' => 'Occitanie',
            'phone' => '',
            'website' => '',
        ],
    ], $overrides);
}

function onboardingInvoicePayload(): array
{
    return array_merge(
        Invoice::factory()->raw([
            'invoice_number' => 'CLIENT-ONBOARDING',
            'taxes' => [Tax::factory()->raw()],
            'items' => [InvoiceItem::factory()->raw()],
        ]),
        ['client_request_id' => (string) Str::uuid()]
    );
}

it('exposes the company setup status in bootstrap', function () {
    getJson('api/v1/bootstrap')
        ->assertOk()
        ->assertJsonPath('company_setup.complete', true)
        ->assertJsonPath('current_company.setup.complete', true);
});

it('creates the French VAT rates and selects 20 percent for a taxable company', function () {
    $response = putJson('api/v1/company', frenchCompanyPayload())
        ->assertOk()
        ->assertJsonPath('company_setup.complete', true)
        ->assertJsonPath('data.vat_regime', 'standard');

    expect($response->json('data.setup.complete'))->toBeTrue();

    foreach ([20.0, 10.0, 5.5, 2.1] as $percent) {
        $this->assertDatabaseHas('tax_types', [
            'company_id' => $this->company->id,
            'percent' => $percent,
        ]);
    }

    $defaultTaxId = (int) CompanySetting::getSetting(
        'autofacture_default_tax_type_id',
        $this->company->id
    );

    expect($defaultTaxId)->toBeGreaterThan(0)
        ->and(TaxType::findOrFail($defaultTaxId)->percent)->toBe(20.0)
        ->and(CompanySetting::getSetting('tax_per_item', $this->company->id))->toBe('YES');
});

it('disables VAT by default for a company under franchise base', function () {
    putJson('api/v1/company', frenchCompanyPayload([
        'vat_regime' => 'franchise_base',
        'vat_number' => null,
    ]))
        ->assertOk()
        ->assertJsonPath('data.vat_regime', 'franchise_base')
        ->assertJsonPath('data.vat_exempt', true);

    $this->assertDatabaseHas('companies', [
        'id' => $this->company->id,
        'vat_regime' => 'franchise_base',
        'vat_exempt' => true,
    ]);

    expect(CompanySetting::getSetting('tax_per_item', $this->company->id))->toBe('NO')
        ->and((int) CompanySetting::getSetting(
            'autofacture_default_tax_type_id',
            $this->company->id
        ))->toBe(0);
});

it('blocks invoice creation while the company setup is incomplete', function () {
    $this->company->forceFill([
        'name' => 'Entreprise de démonstration',
        'siren' => null,
        'siret' => null,
        'vat_regime' => null,
    ])->save();

    $this->company->address()->update([
        'address_street_1' => null,
        'zip' => null,
        'city' => null,
    ]);

    expect(fn () => app(FrenchCompanySetup::class)->assertComplete(
        Company::findOrFail($this->company->id)
    ))->toThrow(ValidationException::class);

    postJson('api/v1/invoices', onboardingInvoicePayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['company_setup']);

    expect(Invoice::query()->count())->toBe(0);
});
