<?php

namespace Crater\Domain\CompanyRegistry;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class CompanyRegistryService
{
    public const SOURCE = 'API Recherche d’entreprises';

    public const SOURCE_URL = 'https://recherche-entreprises.api.gouv.fr';

    public function search(string $query, ?string $postalCode = null, ?string $city = null, int $page = 1): array
    {
        $identifier = preg_replace('/\D+/', '', $query);
        $isIdentifier = in_array(strlen($identifier), [9, 14], true) && $identifier === $query;
        $searchTerm = $isIdentifier ? $identifier : trim($query.' '.($city ?? ''));

        $parameters = [
            'q' => $searchTerm,
            'page' => $page,
            'per_page' => 15,
            'limite_matching_etablissements' => strlen($identifier) === 9 ? 100 : 10,
        ];

        if (! $isIdentifier && filled($postalCode)) {
            $parameters['code_postal'] = $postalCode;
        }

        $payload = $this->request($parameters);

        return $this->normalizePayload($payload, strlen($identifier) === 14 ? $identifier : null);
    }

    public function lookup(string $identifier): array
    {
        $normalized = preg_replace('/\D+/', '', $identifier);

        return $this->search($normalized);
    }

    private function request(array $parameters): array
    {
        $cacheKey = 'company-registry:'.hash('sha256', json_encode($parameters));

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($parameters) {
            return $this->client()
                ->get(self::SOURCE_URL.'/search', $parameters)
                ->throw()
                ->json();
        });
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->withUserAgent('AutoFacture/1.0 (company-registry; contact: support@autofacture.fr)')
            ->connectTimeout(4)
            ->timeout(10)
            ->retry(2, 250, throw: false);
    }

    private function normalizePayload(array $payload, ?string $requestedSiret): array
    {
        $verifiedAt = now()->toIso8601String();
        $results = [];

        foreach (Arr::get($payload, 'results', []) as $company) {
            foreach ($this->establishments($company, $requestedSiret) as $establishment) {
                $results[] = $this->normalizeCompany($company, $establishment, $verifiedAt);
            }
        }

        return [
            'data' => array_values($results),
            'meta' => [
                'page' => (int) Arr::get($payload, 'page', 1),
                'per_page' => (int) Arr::get($payload, 'per_page', 15),
                'total_results' => (int) Arr::get($payload, 'total_results', count($results)),
                'total_pages' => (int) Arr::get($payload, 'total_pages', 1),
                'source' => self::SOURCE,
                'verified_at' => $verifiedAt,
            ],
        ];
    }

    private function establishments(array $company, ?string $requestedSiret): array
    {
        $establishments = Arr::get($company, 'matching_etablissements', []);
        $headOffice = Arr::get($company, 'siege');

        if (is_array($headOffice)) {
            $establishments[] = $headOffice;
        }

        $unique = collect($establishments)
            ->filter(fn ($establishment) => is_array($establishment) && filled(Arr::get($establishment, 'siret')))
            ->unique(fn ($establishment) => Arr::get($establishment, 'siret'));

        if ($requestedSiret !== null) {
            $unique = $unique->filter(fn ($establishment) => Arr::get($establishment, 'siret') === $requestedSiret);
        }

        return $unique->values()->all();
    }

    private function normalizeCompany(array $company, array $establishment, string $verifiedAt): array
    {
        $street = collect([
            Arr::get($establishment, 'complement_adresse'),
            Arr::get($establishment, 'numero_voie'),
            Arr::get($establishment, 'indice_repetition'),
            Arr::get($establishment, 'type_voie'),
            Arr::get($establishment, 'libelle_voie'),
        ])->filter()->implode(' ');

        return [
            'name' => Arr::get($company, 'nom_complet') ?: Arr::get($company, 'nom_raison_sociale'),
            'company_name' => Arr::get($company, 'nom_raison_sociale') ?: Arr::get($company, 'nom_complet'),
            'siren' => (string) Arr::get($company, 'siren'),
            'siret' => (string) Arr::get($establishment, 'siret'),
            'vat_number' => Arr::first(Arr::get($company, 'tva', [])),
            'ape_code' => Arr::get($establishment, 'activite_principale') ?: Arr::get($company, 'activite_principale'),
            'legal_form' => Arr::get($company, 'nature_juridique'),
            'address' => [
                'street' => $street !== '' ? Str::squish($street) : null,
                'full' => Arr::get($establishment, 'adresse'),
                'postal_code' => Arr::get($establishment, 'code_postal'),
                'city' => Arr::get($establishment, 'libelle_commune'),
                'country_code' => filled(Arr::get($establishment, 'code_pays_etranger')) ? null : 'FR',
            ],
            'is_head_office' => (bool) Arr::get($establishment, 'est_siege', false),
            'is_active' => Arr::get($establishment, 'etat_administratif') === 'A',
            'verified_at' => $verifiedAt,
            'source' => self::SOURCE,
        ];
    }
}
