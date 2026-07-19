<?php

namespace Crater\Http\Controllers\V1\Admin\ElectronicInvoicing;

use Crater\Domain\ElectronicInvoicing\Providers\SuperPdpProvider;
use Crater\Http\Controllers\Controller;
use Crater\Models\Company;
use Crater\Models\ElectronicInvoiceConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ElectronicInvoiceConnectionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $company = $this->company($request);
        $connection = $this->connection($company);

        return response()->json($this->payload($company, $connection));
    }

    public function updateProgress(Request $request): JsonResponse
    {
        $company = $this->company($request, ownerOnly: true);
        $validated = $request->validate([
            'account_created' => ['sometimes', 'boolean'],
            'company_verified' => ['sometimes', 'boolean'],
        ]);

        $connection = DB::transaction(function () use ($company, $validated) {
            $connection = $this->connection($company);

            if (($validated['account_created'] ?? false) && ! $connection->account_created_at) {
                $connection->account_created_at = now();
                $connection->setup_step = max(2, $connection->setup_step);
            }

            if (($validated['company_verified'] ?? false) && ! $connection->company_verified_at) {
                $connection->company_verified_at = now();
                $connection->setup_step = max(3, $connection->setup_step);
            }

            $connection->save();

            return $connection->fresh();
        });

        return response()->json($this->payload($company, $connection));
    }

    public function storeCredentials(Request $request): JsonResponse
    {
        $company = $this->company($request, ownerOnly: true);
        $validated = $request->validate([
            'client_id' => ['required', 'string', 'min:4', 'max:255'],
            'client_secret' => ['required', 'string', 'min:8', 'max:4096'],
            'environment' => ['required', Rule::in(['sandbox', 'production'])],
        ]);

        $connection = DB::transaction(function () use ($company, $validated) {
            $connection = $this->connection($company);
            $connection->fill([
                'provider' => ElectronicInvoiceConnection::PROVIDER_SUPERPDP,
                'environment' => $validated['environment'],
                'client_id' => $validated['client_id'],
                'client_secret' => $validated['client_secret'],
                'credentials_configured_at' => now(),
                'connection_status' => ElectronicInvoiceConnection::STATUS_CONFIGURED,
                'setup_step' => 4,
                'last_error_code' => null,
                'last_error_message' => null,
            ]);
            $connection->save();

            return $connection->fresh();
        });

        return response()->json($this->payload($company, $connection));
    }

    public function test(Request $request, SuperPdpProvider $provider): JsonResponse
    {
        $company = $this->company($request, ownerOnly: true);
        $connection = $this->connection($company);
        $result = $provider->testConnection($connection);

        $connection->last_tested_at = now();
        $connection->last_error_code = $result->successful ? null : $result->code;
        $connection->last_error_message = $result->successful ? null : $result->message;

        if ($result->successful) {
            $connection->connection_status = ElectronicInvoiceConnection::STATUS_CONNECTED;
            $connection->last_connected_at = now();
            $connection->external_company_id = $result->metadata['external_company_id'] ?? $connection->external_company_id;
        } elseif ($result->code === 'remote_test_not_configured') {
            $connection->connection_status = ElectronicInvoiceConnection::STATUS_CONFIGURED;
        } else {
            $connection->connection_status = ElectronicInvoiceConnection::STATUS_ERROR;
        }

        $connection->metadata = array_merge($connection->metadata ?? [], $result->metadata);
        $connection->save();

        return response()->json([
            ...$this->payload($company, $connection->fresh()),
            'test' => [
                'successful' => $result->successful,
                'code' => $result->code,
                'message' => $result->message,
            ],
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $company = $this->company($request, ownerOnly: true);
        $connection = $this->connection($company);

        $connection->fill([
            'connection_status' => ElectronicInvoiceConnection::STATUS_DRAFT,
            'setup_step' => 3,
            'client_id' => null,
            'client_secret' => null,
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'external_company_id' => null,
            'credentials_configured_at' => null,
            'last_tested_at' => null,
            'last_connected_at' => null,
            'last_error_code' => null,
            'last_error_message' => null,
            'metadata' => null,
        ]);
        $connection->save();

        return response()->json($this->payload($company, $connection->fresh()));
    }

    private function company(Request $request, bool $ownerOnly = false): Company
    {
        $company = Company::query()
            ->whereKey($request->header('company'))
            ->whereHas('users', fn ($query) => $query->where('users.id', $request->user()->id))
            ->firstOrFail();

        if ($ownerOnly && (int) $company->owner_id !== (int) $request->user()->id) {
            abort(403, 'Seul le propriétaire de l’entreprise peut modifier la connexion à la plateforme agréée.');
        }

        return $company;
    }

    private function connection(Company $company): ElectronicInvoiceConnection
    {
        return ElectronicInvoiceConnection::firstOrCreate(
            ['company_id' => $company->id],
            [
                'provider' => ElectronicInvoiceConnection::PROVIDER_SUPERPDP,
                'environment' => config('electronic-invoicing.providers.superpdp.environment', 'sandbox'),
                'setup_step' => 1,
                'connection_status' => ElectronicInvoiceConnection::STATUS_DRAFT,
            ],
        );
    }

    private function payload(Company $company, ElectronicInvoiceConnection $connection): array
    {
        return [
            'data' => [
                'provider' => $connection->provider,
                'provider_name' => config('electronic-invoicing.providers.superpdp.name', 'SUPER PDP'),
                'environment' => $connection->environment,
                'setup_step' => $connection->setup_step,
                'connection_status' => $connection->connection_status,
                'account_created' => (bool) $connection->account_created_at,
                'company_verified' => (bool) $connection->company_verified_at,
                'has_credentials' => $connection->hasCredentials(),
                'masked_client_id' => $connection->maskedClientId(),
                'last_tested_at' => $connection->last_tested_at?->toIso8601String(),
                'last_connected_at' => $connection->last_connected_at?->toIso8601String(),
                'last_error_code' => $connection->last_error_code,
                'last_error_message' => $connection->last_error_message,
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'siren' => $company->siren,
                'siret' => $company->siret,
                'vat_number' => $company->vat_number,
                'address' => $company->address ? [
                    'address_street_1' => $company->address->address_street_1,
                    'address_street_2' => $company->address->address_street_2,
                    'city' => $company->address->city,
                    'zip' => $company->address->zip,
                    'country_id' => $company->address->country_id,
                ] : null,
            ],
            'links' => [
                'portal' => config('electronic-invoicing.providers.superpdp.portal_url'),
                'documentation' => config('electronic-invoicing.providers.superpdp.documentation_url'),
            ],
            'security' => [
                'identity_documents_collected_by_autofacture' => false,
                'credentials_encrypted' => true,
                'credentials_returned_to_browser' => false,
            ],
        ];
    }
}
