<?php

namespace Crater\Http\Controllers\V1\Admin\CompanyRegistry;

use Crater\Domain\CompanyRegistry\CompanyRegistryService;
use Crater\Http\Controllers\Controller;
use Crater\Http\Requests\CompanyRegistrySearchRequest;
use Crater\Models\Customer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class CompanyRegistryController extends Controller
{
    public function search(CompanyRegistrySearchRequest $request, CompanyRegistryService $registry): JsonResponse
    {
        $this->authorize('create', Customer::class);

        try {
            $result = $registry->search(
                $request->string('q')->toString(),
                $request->input('postal_code'),
                $request->input('city'),
                $request->integer('page', 1),
            );

            return response()->json($this->addDuplicateInformation($result, $request));
        } catch (ConnectionException) {
            return response()->json([
                'message' => 'Le service officiel des entreprises est temporairement inaccessible. Réessayez dans quelques instants.',
                'action' => 'Vous pouvez continuer à saisir le client manuellement.',
            ], 503);
        } catch (RequestException $exception) {
            $status = $exception->response?->status();

            return response()->json([
                'message' => $status === 429
                    ? 'Le service officiel reçoit trop de demandes pour le moment.'
                    : 'La recherche officielle n’a pas pu aboutir.',
                'action' => 'Patientez quelques instants ou continuez la saisie manuellement.',
            ], $status === 429 ? 429 : 502);
        }
    }

    private function addDuplicateInformation(array $result, CompanyRegistrySearchRequest $request): array
    {
        $companyId = (int) $request->header('company');
        $excludeCustomerId = $request->integer('exclude_customer_id');
        $sirets = collect($result['data'])->pluck('siret')->filter()->unique();

        $duplicates = Customer::query()
            ->where('company_id', $companyId)
            ->whereIn('siret', $sirets)
            ->when($excludeCustomerId, fn ($query) => $query->where('id', '!=', $excludeCustomerId))
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

        return $result;
    }
}
