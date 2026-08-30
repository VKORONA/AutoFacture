<?php

namespace Crater\Http\Controllers\V1\Admin\ElectronicInvoicing;

use Crater\Domain\ElectronicInvoicing\Providers\SuperPdpProvider;
use Crater\Http\Controllers\Controller;
use Crater\Models\Company;
use Crater\Models\ElectronicInvoiceConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SuperPdpOAuthController extends Controller
{
    public function start(Request $request, SuperPdpProvider $provider): JsonResponse
    {
        $company = Company::query()
            ->whereKey($request->header('company'))
            ->where('owner_id', $request->user()->id)
            ->firstOrFail();
        $connection = ElectronicInvoiceConnection::query()
            ->where('company_id', $company->id)
            ->firstOrFail();

        abort_unless($connection->hasCredentials(), 422, 'Enregistrez les identifiants SUPER PDP avant l’autorisation.');
        abort_unless($provider->oauthConfigured(), 503, 'La configuration OAuth officielle SUPER PDP est incomplète.');

        $state = Str::random(64);
        $authorizationUrl = $provider->authorizationUrl($connection, $state);

        abort_unless(filled($authorizationUrl), 503, 'Le parcours OAuth SUPER PDP ne peut pas être initialisé.');

        $connection->fill([
            'oauth_state_hash' => hash('sha256', $state),
            'oauth_state_expires_at' => now()->addMinutes(10),
            'connection_status' => ElectronicInvoiceConnection::STATUS_AUTHORIZATION_PENDING,
            'last_error_code' => null,
            'last_error_message' => null,
        ])->save();

        return response()->json([
            'authorization_url' => $authorizationUrl,
            'expires_at' => $connection->oauth_state_expires_at->toIso8601String(),
        ]);
    }

    public function callback(Request $request, SuperPdpProvider $provider): RedirectResponse
    {
        $state = (string) $request->query('state');
        $connection = filled($state)
            ? ElectronicInvoiceConnection::query()
                ->where('oauth_state_hash', hash('sha256', $state))
                ->where('oauth_state_expires_at', '>=', now())
                ->first()
            : null;

        if (! $connection) {
            return $this->redirectWithStatus('error', 'invalid_or_expired_state');
        }

        $connection->oauth_state_hash = null;
        $connection->oauth_state_expires_at = null;

        if ($request->filled('error') || ! $request->filled('code')) {
            $connection->connection_status = ElectronicInvoiceConnection::STATUS_CREDENTIALS_SAVED;
            $connection->last_error_code = (string) $request->query('error', 'authorization_cancelled');
            $connection->last_error_message = 'L’autorisation SUPER PDP a été annulée ou refusée.';
            $connection->save();

            return $this->redirectWithStatus('error', $connection->last_error_code);
        }

        $exchange = $provider->exchangeAuthorizationCode($connection, (string) $request->query('code'));

        if (! $exchange->successful) {
            $connection->connection_status = ElectronicInvoiceConnection::STATUS_CONNECTION_LOST;
            $connection->last_error_code = $exchange->code;
            $connection->last_error_message = $exchange->message;
            $connection->save();

            return $this->redirectWithStatus('error', $exchange->code);
        }

        $test = $provider->testConnection($connection);
        $connection->last_tested_at = now();
        $connection->last_error_code = $test->successful ? null : $test->code;
        $connection->last_error_message = $test->successful ? null : $test->message;
        $connection->metadata = array_merge($connection->metadata ?? [], $test->metadata);

        if ($test->successful) {
            $connection->connection_status = ElectronicInvoiceConnection::STATUS_CONNECTED;
            $connection->setup_step = 5;
            $connection->last_connected_at = now();
            $connection->external_company_id = $test->metadata['external_company_id']
                ?? $connection->external_company_id;
        } else {
            $connection->connection_status = $test->code === 'token_refresh_required'
                ? ElectronicInvoiceConnection::STATUS_TOKEN_REFRESH_REQUIRED
                : ElectronicInvoiceConnection::STATUS_CONNECTION_LOST;
        }

        $connection->save();

        return $this->redirectWithStatus(
            $test->successful ? 'connected' : 'error',
            $test->successful ? null : $test->code,
        );
    }

    private function redirectWithStatus(string $status, ?string $reason = null): RedirectResponse
    {
        $query = array_filter([
            'superpdp' => $status,
            'reason' => $reason,
        ]);

        return redirect('/admin/electronic-invoicing?'.http_build_query($query));
    }
}
