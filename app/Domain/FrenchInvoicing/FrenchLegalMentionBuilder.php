<?php

namespace Crater\Domain\FrenchInvoicing;

use Crater\Models\Company;

class FrenchLegalMentionBuilder
{
    public function forCompany(Company $company): array
    {
        $mentions = [];

        if ($company->legal_form) {
            $mentions[] = $company->legal_form;
        }

        if ($company->siren) {
            $mentions[] = 'SIREN '.$company->siren;
        }

        if ($company->siret) {
            $mentions[] = 'SIRET '.$company->siret;
        }

        if ($company->rcs_city) {
            $mentions[] = 'RCS '.$company->rcs_city;
        }

        if ($company->ape_code) {
            $mentions[] = 'Code APE '.$company->ape_code;
        }

        if ($company->vat_number) {
            $mentions[] = 'TVA intracommunautaire '.$company->vat_number;
        }

        if ($company->share_capital !== null && (float) $company->share_capital > 0) {
            $mentions[] = 'Capital social '.number_format((float) $company->share_capital, 2, ',', ' ').' €';
        }

        if ($company->vat_regime === 'franchise_base') {
            $mentions[] = 'TVA non applicable, art. 293 B du CGI';
        }

        return array_values(array_unique($mentions));
    }

    public function paymentDetails(Company $company): array
    {
        return array_filter([
            'iban' => $company->iban,
            'bic' => $company->bic,
        ]);
    }
}
