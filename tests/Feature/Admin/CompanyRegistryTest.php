<?php

use Crater\Models\Customer;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->user = User::findOrFail(1);
    $this->companyId = $this->user->companies()->first()->id;
    $this->withHeaders(['company' => $this->companyId]);
    Sanctum::actingAs($this->user, ['*']);
    Cache::clear();
});

function registryPayload(): array
{
    return [
        'results' => [[
            'siren' => '418166096',
            'nom_complet' => 'OCTO-TECHNOLOGY',
            'nom_raison_sociale' => 'OCTO-TECHNOLOGY',
            'nature_juridique' => '5710',
            'activite_principale' => '62.02A',
            'etat_administratif' => 'A',
            'tva' => ['FR16418166096'],
            'matching_etablissements' => [],
            'siege' => [
                'siret' => '41816609600069',
                'activite_principale' => '62.02A',
                'adresse' => "34 AVENUE DE L'OPERA 75002 PARIS",
                'numero_voie' => '34',
                'type_voie' => 'AVENUE',
                'libelle_voie' => "DE L'OPERA",
                'code_postal' => '75002',
                'libelle_commune' => 'PARIS',
                'code_pays_etranger' => null,
                'est_siege' => true,
                'etat_administratif' => 'A',
            ],
        ]],
        'total_results' => 1,
        'page' => 1,
        'per_page' => 15,
        'total_pages' => 1,
    ];
}

test('searches the official registry through the Laravel API', function () {
    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response(registryPayload()),
    ]);

    getJson('/api/v1/company-registry/search?q=418166096')
        ->assertOk()
        ->assertJsonPath('data.0.company_name', 'OCTO-TECHNOLOGY')
        ->assertJsonPath('data.0.siren', '418166096')
        ->assertJsonPath('data.0.siret', '41816609600069')
        ->assertJsonPath('data.0.vat_number', 'FR16418166096')
        ->assertJsonPath('data.0.ape_code', '62.02A')
        ->assertJsonPath('data.0.legal_form', '5710')
        ->assertJsonPath('data.0.address.street', "34 AVENUE DE L'OPERA")
        ->assertJsonPath('data.0.address.postal_code', '75002')
        ->assertJsonPath('data.0.address.city', 'PARIS')
        ->assertJsonPath('data.0.is_active', true)
        ->assertJsonPath('data.0.duplicate_customer', null)
        ->assertJsonPath('meta.source', 'API Recherche d’entreprises');

    Http::assertSent(fn ($request) => $request['q'] === '418166096'
        && $request['limite_matching_etablissements'] === 100
        && str_contains($request->header('User-Agent')[0], 'AutoFacture'));
});

test('reports an existing customer with the same company and siret', function () {
    Http::fake([
        'recherche-entreprises.api.gouv.fr/*' => Http::response(registryPayload()),
    ]);

    $customer = Customer::factory()->create([
        'company_id' => $this->companyId,
        'name' => 'Client existant',
        'siren' => '418166096',
        'siret' => '41816609600069',
    ]);

    getJson('/api/v1/company-registry/search?q=41816609600069')
        ->assertOk()
        ->assertJsonPath('data.0.duplicate_customer.id', $customer->id)
        ->assertJsonPath('data.0.duplicate_customer.name', 'Client existant');
});

test('validates company registry search filters', function () {
    getJson('/api/v1/company-registry/search?q=a&postal_code=750')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q', 'postal_code']);

    getJson('/api/v1/company-registry/lookup/not-a-number')
        ->assertNotFound();
});

test('prevents duplicate siret when creating a customer', function () {
    Customer::factory()->create([
        'company_id' => $this->companyId,
        'siren' => '418166096',
        'siret' => '41816609600069',
    ]);

    $payload = Customer::factory()->raw([
        'customer_type' => 'business',
        'siren' => '418166096',
        'siret' => '41816609600069',
    ]);

    postJson('/api/v1/customers', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['siret']);
});
