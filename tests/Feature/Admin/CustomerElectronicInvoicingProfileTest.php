<?php

use Crater\Models\Currency;
use Crater\Models\Customer;
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

    $this->withHeaders(['company' => $this->company->id]);
    Sanctum::actingAs($this->user, ['*']);
});

it('marks a private customer as B2C without B2B electronic invoicing', function () {
    $customer = Customer::factory()->create([
        'company_id' => $this->company->id,
        'customer_type' => 'individual',
        'company_name' => null,
        'siren' => null,
        'siret' => null,
        'vat_number' => null,
        'electronic_invoicing_email' => null,
    ]);

    getJson("/api/v1/customers/{$customer->id}")
        ->assertOk()
        ->assertJsonPath('data.customer_type_label', 'Particulier')
        ->assertJsonPath('data.electronic_invoicing_profile.customer_category', 'b2c')
        ->assertJsonPath('data.electronic_invoicing_profile.document_mode', 'standard_pdf')
        ->assertJsonPath('data.electronic_invoicing_profile.delivery_channel', 'direct_customer_delivery')
        ->assertJsonPath('data.electronic_invoicing_profile.requires_approved_platform', false)
        ->assertJsonPath('data.electronic_invoicing_profile.e_invoicing_applicable', false)
        ->assertJsonPath('data.electronic_invoicing_profile.e_reporting_applicable', true)
        ->assertJsonPath('data.electronic_invoicing_profile.status', 'not_applicable_b2c');
});

it('marks an identified professional customer as ready for structured invoicing', function () {
    $customer = Customer::factory()->create([
        'company_id' => $this->company->id,
        'customer_type' => 'business',
        'siret' => '73282932000074',
    ]);

    getJson("/api/v1/customers/{$customer->id}")
        ->assertOk()
        ->assertJsonPath('data.customer_type_label', 'Professionnel')
        ->assertJsonPath('data.electronic_invoicing_profile.customer_category', 'b2b')
        ->assertJsonPath('data.electronic_invoicing_profile.document_mode', 'factur_x_ready')
        ->assertJsonPath('data.electronic_invoicing_profile.delivery_channel', 'approved_platform')
        ->assertJsonPath('data.electronic_invoicing_profile.requires_approved_platform', true)
        ->assertJsonPath('data.electronic_invoicing_profile.e_invoicing_applicable', true)
        ->assertJsonPath('data.electronic_invoicing_profile.status', 'ready');
});

it('removes professional identifiers when a private customer is submitted', function () {
    $currencyId = Currency::query()->value('id');

    expect($currencyId)->not->toBeNull();

    postJson('/api/v1/customers', [
        'name' => 'Marie Dupont',
        'customer_type' => 'individual',
        'currency_id' => $currencyId,
        'company_name' => 'Ancienne société',
        'siren' => '732829320',
        'siret' => '73282932000074',
        'vat_number' => 'FR44732829320',
        'ape_code' => '4322B',
        'electronic_invoicing_email' => 'facturation@example.test',
        'enable_portal' => false,
        'billing' => [],
        'shipping' => [],
    ])->assertOk();

    $customer = Customer::query()->where('name', 'Marie Dupont')->firstOrFail();

    expect($customer->customer_type)->toBe('individual')
        ->and($customer->company_name)->toBeNull()
        ->and($customer->siren)->toBeNull()
        ->and($customer->siret)->toBeNull()
        ->and($customer->vat_number)->toBeNull()
        ->and($customer->ape_code)->toBeNull()
        ->and($customer->electronic_invoicing_email)->toBeNull();
});
