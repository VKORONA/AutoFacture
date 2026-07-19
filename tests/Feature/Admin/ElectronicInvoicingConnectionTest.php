<?php

use Crater\Models\ElectronicInvoiceConnection;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
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

it('starts with a safe draft connection and never collects identity documents', function () {
    getJson('/api/v1/electronic-invoicing/connection')
        ->assertOk()
        ->assertJsonPath('data.provider', 'superpdp')
        ->assertJsonPath('data.connection_status', 'draft')
        ->assertJsonPath('data.account_created', false)
        ->assertJsonPath('data.company_verified', false)
        ->assertJsonPath('data.has_credentials', false)
        ->assertJsonPath('security.identity_documents_collected_by_autofacture', false)
        ->assertJsonPath('security.credentials_encrypted', true)
        ->assertJsonPath('security.credentials_returned_to_browser', false);
});

it('memorizes the guided setup progress for the current company', function () {
    postJson('/api/v1/electronic-invoicing/connection/progress', [
        'account_created' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.account_created', true)
        ->assertJsonPath('data.setup_step', 2);

    postJson('/api/v1/electronic-invoicing/connection/progress', [
        'company_verified' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.company_verified', true)
        ->assertJsonPath('data.setup_step', 3);

    getJson('/api/v1/electronic-invoicing/connection')
        ->assertOk()
        ->assertJsonPath('data.account_created', true)
        ->assertJsonPath('data.company_verified', true);
});

it('encrypts the SUPER PDP codes and never returns the secret to the browser', function () {
    postJson('/api/v1/electronic-invoicing/connection/progress', [
        'account_created' => true,
        'company_verified' => true,
    ])->assertOk();

    $clientId = 'autofacture-client-123456';
    $clientSecret = 'super-secret-value-that-must-never-leak';

    putJson('/api/v1/electronic-invoicing/connection/credentials', [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'environment' => 'sandbox',
    ])
        ->assertOk()
        ->assertJsonPath('data.has_credentials', true)
        ->assertJsonPath('data.connection_status', 'configured')
        ->assertJsonMissingPath('data.client_id')
        ->assertJsonMissingPath('data.client_secret');

    $raw = DB::table('electronic_invoice_connections')
        ->where('company_id', $this->company->id)
        ->first();

    expect($raw)->not->toBeNull()
        ->and($raw->client_id)->not->toBe($clientId)
        ->and($raw->client_secret)->not->toBe($clientSecret)
        ->and($raw->client_id)->not->toContain($clientId)
        ->and($raw->client_secret)->not->toContain($clientSecret);

    $connection = ElectronicInvoiceConnection::query()
        ->where('company_id', $this->company->id)
        ->firstOrFail();

    expect($connection->client_id)->toBe($clientId)
        ->and($connection->client_secret)->toBe($clientSecret);

    getJson('/api/v1/electronic-invoicing/connection')
        ->assertOk()
        ->assertJsonPath('data.has_credentials', true)
        ->assertJsonMissingPath('data.client_secret')
        ->assertJsonMissingPath('data.client_id');
});

it('keeps encrypted codes configured while the official remote test is not set', function () {
    config()->set('electronic-invoicing.providers.superpdp.test_url', null);

    postJson('/api/v1/electronic-invoicing/connection/progress', [
        'account_created' => true,
        'company_verified' => true,
    ])->assertOk();

    putJson('/api/v1/electronic-invoicing/connection/credentials', [
        'client_id' => 'autofacture-client-test',
        'client_secret' => 'autofacture-secret-test',
        'environment' => 'sandbox',
    ])->assertOk();

    postJson('/api/v1/electronic-invoicing/connection/test')
        ->assertOk()
        ->assertJsonPath('data.connection_status', 'configured')
        ->assertJsonPath('test.successful', false)
        ->assertJsonPath('test.code', 'remote_test_not_configured');
});

it('can remove the technical liaison without deleting the onboarding progress', function () {
    postJson('/api/v1/electronic-invoicing/connection/progress', [
        'account_created' => true,
        'company_verified' => true,
    ])->assertOk();

    putJson('/api/v1/electronic-invoicing/connection/credentials', [
        'client_id' => 'autofacture-client-test',
        'client_secret' => 'autofacture-secret-test',
        'environment' => 'sandbox',
    ])->assertOk();

    deleteJson('/api/v1/electronic-invoicing/connection')
        ->assertOk()
        ->assertJsonPath('data.connection_status', 'draft')
        ->assertJsonPath('data.setup_step', 3)
        ->assertJsonPath('data.account_created', true)
        ->assertJsonPath('data.company_verified', true)
        ->assertJsonPath('data.has_credentials', false);
});
