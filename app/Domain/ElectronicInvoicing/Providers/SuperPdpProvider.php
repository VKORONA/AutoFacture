<?php

namespace Crater\Domain\ElectronicInvoicing\Providers;

use Crater\Domain\ElectronicInvoicing\Contracts\ElectronicInvoiceProvider;
use Crater\Domain\ElectronicInvoicing\Data\ProviderConnectionResult;
use Crater\Models\ElectronicInvoiceConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SuperPdpProvider implements ElectronicInvoiceProvider
{
    public function code(): string
    {
        return ElectronicInvoiceConnection::PROVIDER_SUPERPDP;
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

        $testUrl = config('electronic-invoicing.providers.superpdp.test_url');
        $clientIdHeader = config('electronic-invoicing.providers.superpdp.client_id_header');
        $clientSecretHeader = config('electronic-invoicing.providers.superpdp.client_secret_header');

        if (! filled($testUrl) || ! filled($clientIdHeader) || ! filled($clientSecretHeader)) {
            return new ProviderConnectionResult(
                false,
                'remote_test_not_configured',
                'Les codes sont chiffrés et enregistrés. Le test distant sera activé dès que les paramètres officiels du bac à sable SUPER PDP seront configurés.',
            );
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('electronic-invoicing.providers.superpdp.timeout', 10))
                ->withHeaders([
                    $clientIdHeader => $connection->client_id,
                    $clientSecretHeader => $connection->client_secret,
                ])
                ->get($testUrl);
        } catch (ConnectionException) {
            return new ProviderConnectionResult(
                false,
                'provider_unreachable',
                'SUPER PDP est temporairement inaccessible. Les codes enregistrés n’ont pas été modifiés.',
            );
        }

        if (! $response->successful()) {
            return new ProviderConnectionResult(
                false,
                'provider_rejected_credentials',
                'SUPER PDP a refusé la connexion. Vérifiez que les codes correspondent à la bonne entreprise et au bon environnement.',
                ['http_status' => $response->status()],
            );
        }

        $payload = $response->json();

        return new ProviderConnectionResult(
            true,
            'connected',
            'Connexion SUPER PDP validée.',
            [
                'external_company_id' => data_get($payload, 'company.id'),
                'company_verification_status' => data_get($payload, 'company_verification_status'),
            ],
        );
    }
}
