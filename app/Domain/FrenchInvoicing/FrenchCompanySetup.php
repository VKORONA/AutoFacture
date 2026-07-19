<?php

namespace Crater\Domain\FrenchInvoicing;

use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\TaxType;

final class FrenchCompanySetup
{
    /**
     * @return array{complete: bool, missing_fields: array<int, string>, vat_regime: ?string}
     */
    public function status(Company $company): array
    {
        $company->loadMissing('address');
        $missing = [];
        $address = $company->address;

        if (
            blank($company->name)
            || mb_strtolower(trim((string) $company->name)) === 'entreprise de démonstration'
        ) {
            $missing[] = 'Nom réel de l’entreprise';
        }

        if (! preg_match('/^\d{14}$/', (string) $company->siret)) {
            $missing[] = 'SIRET à 14 chiffres';
        }

        if (! in_array($company->vat_regime, ['standard', 'franchise_base', 'exempt'], true)) {
            $missing[] = 'Régime de TVA';
        }

        if (! $address || blank($address->address_street_1)) {
            $missing[] = 'Adresse';
        }

        if (! $address || blank($address->zip)) {
            $missing[] = 'Code postal';
        }

        if (! $address || blank($address->city)) {
            $missing[] = 'Ville';
        }

        if (! $address || ! $address->country_id) {
            $missing[] = 'Pays';
        }

        return [
            'complete' => $missing === [],
            'missing_fields' => $missing,
            'vat_regime' => $company->vat_regime,
        ];
    }

    public function synchronizeVatDefaults(Company $company): void
    {
        $isTaxable = $company->vat_regime === 'standard';
        $defaultTaxTypeId = 0;

        if ($isTaxable) {
            foreach ([
                ['name' => 'TVA 20 %', 'percent' => 20.0],
                ['name' => 'TVA 10 %', 'percent' => 10.0],
                ['name' => 'TVA 5,5 %', 'percent' => 5.5],
                ['name' => 'TVA 2,1 %', 'percent' => 2.1],
            ] as $tax) {
                $taxType = TaxType::query()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'name' => $tax['name'],
                    ],
                    [
                        'percent' => $tax['percent'],
                        'compound_tax' => false,
                        'type' => TaxType::TYPE_GENERAL,
                    ]
                );

                if ($tax['percent'] === 20.0) {
                    $defaultTaxTypeId = $taxType->id;
                }
            }
        }

        CompanySetting::setSettings([
            'tax_per_item' => $isTaxable ? 'YES' : 'NO',
            'autofacture_default_tax_type_id' => (string) $defaultTaxTypeId,
            'autofacture_default_tax_percent' => $isTaxable ? '20' : '0',
            'autofacture_vat_configured' => 'YES',
        ], $company->id);
    }
}
