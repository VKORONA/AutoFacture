<?php

namespace Crater\Domain\ElectronicInvoicing\Providers;

use Crater\Domain\ElectronicInvoicing\Contracts\ElectronicInvoiceProvider;
use Crater\Domain\ElectronicInvoicing\Data\ProviderConnectionResult;
use Crater\Models\ElectronicInvoiceConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SuperPdpProvider implements ElectronicInvoiceProvider
{
    public function code(): string
    {
        return ElectronicInvoiceConnection::PROVIDER_SUPERPDP;
    }

    public function oauthConfigured(): bool
    {
        return filled($this->config('authorize_url'))
            && filled($this->config('token_url'))
            && filled($this->config('session_url'));
    }

    public function authorizationUrl(ElectronicInvoiceConnection $connection, string $state): ?string
    {
        if (! $this->oauthConfigured() || ! $connection->hasCredentials()) {
            return null;
        }

        $query = [
            'response_type' => 'code',
            'client_id' => $connection->client_id,
            'redirect_uri' => route('electronic-invoicing.oauth.callback'),
            'state' => $state,
        ];
        $scopes = $this->config('scopes', []);

        if ($scopes !== []) {
            $query['scope'] = implode(' ', $scopes);
        }

        return rtrim((string) $this->config('authorize_url'), '?')
            .'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(
        ElectronicInvoiceConnection $connection,
        string $code,
    ): ProviderConnectionResult {
        return $this->requestTokens($connection, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('electronic-invoicing.oauth.callback'),
        ]);
    }

    public function testConnection(ElectronicInvoiceConnection $connection): ProviderConnectionResult
    {
        if (! $connection->hasCredentials()) {
            return new ProviderConnectionResult(
                false,
                'credentials_missing',
                'Les codes de liaison SUPER PDP ne sont pas encore enregistrés.',
            );
        }

        if (! $this->oauthConfigured()) {
            return new ProviderConnectionResult(
                false,
                'oauth_not_configured',
                'Les URL OAuth officielles SUPER PDP doivent être configurées avant toute connexion.',
            );
        }

        if (! filled($connection->access_token)) {
            return new ProviderConnectionResult(
                false,
                'authorization_required',
                'Les codes sont enregistrés. Autorisez maintenant AutoFacture depuis SUPER PDP.',
            );
        }

        if ($connection->token_expires_at?->lessThanOrEqualTo(now()->addMinute())) {
            $refresh = $this->refreshAccessToken($connection);

            if (! $refresh->successful) {
                return $refresh;
            }
        }

        try {
            $response = Http::acceptJson()
                ->withToken($connection->access_token)
                ->timeout((int) $this->config('timeout', 10))
                ->get((string) $this->config('session_url'));
        } catch (ConnectionException) {
            return new ProviderConnectionResult(
                false,
                'provider_unreachable',
                'SUPER PDP est temporairement inaccessible. La liaison a été conservée.',
            );
        }

        if ($response->status() === 401) {
            return new ProviderConnectionResult(
                false,
                'token_refresh_required',
                'La session SUPER PDP a expiré. Une nouvelle autorisation est nécessaire.',
            );
        }

        if (! $response->successful()) {
            return new ProviderConnectionResult(
                false,
                'provider_connection_lost',
                'SUPER PDP a refusé le contrôle de session.',
                ['http_status' => $response->status()],
            );
        }

        $payload = $response->json();

        return new ProviderConnectionResult(
            true,
            'connected',
            'Connexion SUPER PDP validée.',
            [
                'external_company_id' => data_get($payload, 'company.id') ?? data_get($payload, 'id'),
                'company_verification_status' => data_get($payload, 'company_verification_status'),
            ],
        );
    }

    private function refreshAccessToken(ElectronicInvoiceConnection $connection): ProviderConnectionResult
    {
        if (! filled($connection->refresh_token)) {
            return new ProviderConnectionResult(
                false,
                'token_refresh_required',
                'Le jeton de renouvellement est absent. Une nouvelle autorisation est nécessaire.',
            );
        }

        return $this->requestTokens($connection, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
        ]);
    }

    private function requestTokens(
        ElectronicInvoiceConnection $connection,
        array $grant,
    ): ProviderConnectionResult {
        if (! $this->oauthConfigured()) {
            return new ProviderConnectionResult(
                false,
                'oauth_not_configured',
                'Les URL OAuth officielles SUPER PDP ne sont pas configurées.',
            );
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) $this->config('timeout', 10))
                ->post((string) $this->config('token_url'), [
                    ...$grant,
                    'client_id' => $connection->client_id,
                    'client_secret' => $connection->client_secret,
                ]);
        } catch (ConnectionException) {
            return new ProviderConnectionResult(
                false,
                'provider_unreachable',
                'SUPER PDP est temporairement inaccessible. Réessayez sans modifier les identifiants.',
            );
        }

        return $this->tokenResult($connection, $response);
    }

    private function tokenResult(
        ElectronicInvoiceConnection $connection,
        Response $response,
    ): ProviderConnectionResult {
        if (! $response->successful() || ! filled($response->json('access_token'))) {
            return new ProviderConnectionResult(
                false,
                'authorization_exchange_failed',
                'SUPER PDP a refusé l’autorisation. Vérifiez l’application et l’URL de redirection.',
                ['http_status' => $response->status()],
            );
        }

        $connection->access_token = $response->json('access_token');
        $connection->refresh_token = $response->json('refresh_token') ?: $connection->refresh_token;
        $connection->token_expires_at = now()->addSeconds(max(60, (int) $response->json('expires_in', 3600)));
        $connection->metadata = array_merge($connection->metadata ?? [], [
            'oauth_token_type' => $response->json('token_type', 'Bearer'),
            'oauth_scope' => $response->json('scope'),
        ]);

        return new ProviderConnectionResult(
            true,
            'authorized',
            'Autorisation SUPER PDP enregistrée.',
        );
    }

    private function config(string $key, mixed $default = null): mixed
    {
        return config("electronic-invoicing.providers.superpdp.{$key}", $default);
    }
}
