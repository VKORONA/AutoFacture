<?php

use Crater\Models\ElectronicInvoiceConnection;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
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
        ->assertJsonPath('data.connection_status', 'not_configured')
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
        ->assertJsonPath('data.connection_status', 'credentials_saved')
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

it('keeps encrypted codes configured while the official oauth endpoints are not set', function () {
    config()->set('electronic-invoicing.providers.superpdp.authorize_url', null);
    config()->set('electronic-invoicing.providers.superpdp.token_url', null);
    config()->set('electronic-invoicing.providers.superpdp.session_url', null);

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
        ->assertJsonPath('data.connection_status', 'credentials_saved')
        ->assertJsonPath('test.successful', false)
        ->assertJsonPath('test.code', 'oauth_not_configured');
});

it('uses a short lived state and completes the oauth authorization code flow', function () {
    config()->set('electronic-invoicing.providers.superpdp.authorize_url', 'https://superpdp.test/oauth/authorize');
    config()->set('electronic-invoicing.providers.superpdp.token_url', 'https://superpdp.test/oauth/token');
    config()->set('electronic-invoicing.providers.superpdp.session_url', 'https://superpdp.test/api/session');
    config()->set('electronic-invoicing.providers.superpdp.scopes', ['invoices.read', 'invoices.write']);

    Http::fake([
        'https://superpdp.test/oauth/token' => Http::response([
            'access_token' => 'access-token-that-must-be-encrypted',
            'refresh_token' => 'refresh-token-that-must-be-encrypted',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scope' => 'invoices.read invoices.write',
        ]),
        'https://superpdp.test/api/session' => Http::response([
            'company' => ['id' => 'superpdp-company-42'],
            'company_verification_status' => 'verified',
        ]),
    ]);

    postJson('/api/v1/electronic-invoicing/connection/progress', [
        'account_created' => true,
        'company_verified' => true,
    ])->assertOk();

    putJson('/api/v1/electronic-invoicing/connection/credentials', [
        'client_id' => 'oauth-client-id',
        'client_secret' => 'oauth-client-secret',
        'environment' => 'sandbox',
    ])->assertOk();

    $authorizationUrl = postJson('/api/v1/electronic-invoicing/oauth/start')
        ->assertOk()
        ->assertJsonPath('authorization_url', fn ($url) => str_starts_with($url, 'https://superpdp.test/oauth/authorize?'))
        ->json('authorization_url');

    parse_str((string) parse_url($authorizationUrl, PHP_URL_QUERY), $authorizationQuery);

    expect($authorizationQuery['state'])->toHaveLength(64)
        ->and($authorizationQuery['scope'])->toBe('invoices.read invoices.write');

    get('/api/v1/electronic-invoicing/oauth/callback?'.http_build_query([
        'state' => $authorizationQuery['state'],
        'code' => 'one-use-authorization-code',
    ]))->assertRedirect('/admin/electronic-invoicing?superpdp=connected');

    $raw = DB::table('electronic_invoice_connections')
        ->where('company_id', $this->company->id)
        ->first();
    $connection = ElectronicInvoiceConnection::query()
        ->where('company_id', $this->company->id)
        ->firstOrFail();

    expect($raw->access_token)->not->toBe('access-token-that-must-be-encrypted')
        ->and($raw->refresh_token)->not->toBe('refresh-token-that-must-be-encrypted')
        ->and($raw->oauth_state_hash)->toBeNull()
        ->and($connection->connection_status)->toBe('connected')
        ->and($connection->external_company_id)->toBe('superpdp-company-42');
});

it('rejects an expired or unknown oauth state', function () {
    get('/api/v1/electronic-invoicing/oauth/callback?state=invalid&code=ignored')
        ->assertRedirect('/admin/electronic-invoicing?superpdp=error&reason=invalid_or_expired_state');
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
        ->assertJsonPath('data.connection_status', 'not_configured')
        ->assertJsonPath('data.setup_step', 3)
        ->assertJsonPath('data.account_created', true)
        ->assertJsonPath('data.company_verified', true)
        ->assertJsonPath('data.has_credentials', false);
});
