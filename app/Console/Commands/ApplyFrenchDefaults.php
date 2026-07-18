<?php

namespace Crater\Console\Commands;

use Crater\Domain\FrenchInvoicing\FrenchCompanyDefaults;
use Crater\Models\Company;
use Illuminate\Console\Command;

class ApplyFrenchDefaults extends Command
{
    protected $signature = 'autofacture:apply-french-defaults {--company= : Identifiant d’une entreprise précise}';

    protected $description = 'Applique les paramètres français recommandés à AutoFacture';

    public function handle(FrenchCompanyDefaults $defaults)
    {
        $companies = Company::query()
            ->when($this->option('company'), function ($query, $companyId) {
                $query->whereKey($companyId);
            })
            ->get();

        if ($companies->isEmpty()) {
            $this->warn('Aucune entreprise à configurer.');
            return 0;
        }

        foreach ($companies as $company) {
            $defaults->apply($company);
            $this->info("Paramètres français appliqués à {$company->name} (ID {$company->id}).");
        }

        return 0;
    }
}
