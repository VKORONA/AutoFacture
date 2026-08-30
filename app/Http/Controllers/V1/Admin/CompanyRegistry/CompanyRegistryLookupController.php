<?php

namespace Crater\Http\Controllers\V1\Admin\CompanyRegistry;

use Crater\Domain\CompanyRegistry\CompanyRegistryService;
use Crater\Http\Controllers\Controller;
use Crater\Models\Customer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class CompanyRegistryLookupController extends Controller
{
    public function __invoke(string $identifier, CompanyRegistryService $registry): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $normalized = preg_replace('/\D+/', '', $identifier);
        if (! in_array(strlen($normalized), [9, 14], true)) {
            return response()->json([
                'message' => 'Saisissez un SIREN de 9 chiffres ou un SIRET de 14 chiffres.',
            ], 422);
        }

        try {
            $result = $registry->lookup($normalized);
            $companyId = (int) request()->header('company');
            $duplicates = Customer::query()
                ->where('company_id', $companyId)
                ->whereIn('siret', collect($result['data'])->pluck('siret')->filter())
                ->get(['id', 'name', 'siret'])
                ->keyBy('siret');

            $result['data'] = collect($result['data'])->map(function (array $company) use ($duplicates) {
                $duplicate = $duplicates->get($company['siret']);
                $company['duplicate_customer'] = $duplicate ? [
                    'id' => $duplicate->id,
                    'name' => $duplicate->name,
                ] : null;

                return $company;
            })->all();

            return response()->json($result);
        } catch (ConnectionException) {
            return response()->json([
                'message' => 'Le service officiel des entreprises est temporairement inaccessible.',
                'action' => 'Réessayez dans quelques instants ou saisissez le client manuellement.',
            ], 503);
        } catch (RequestException $exception) {
            return response()->json([
                'message' => 'La vérification officielle n’a pas pu aboutir.',
                'action' => 'Vérifiez le numéro ou continuez la saisie manuellement.',
            ], $exception->response?->status() === 429 ? 429 : 502);
        }
    }
}
