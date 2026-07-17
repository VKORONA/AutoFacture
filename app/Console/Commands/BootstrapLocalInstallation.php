<?php

namespace Crater\Console\Commands;

use Crater\Domain\FrenchInvoicing\FrenchCompanyDefaults;
use Crater\Models\Company;
use Crater\Models\Setting;
use Crater\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BootstrapLocalInstallation extends Command
{
    protected $signature = 'autofacture:bootstrap-local';

    protected $description = 'Finalise une installation locale après les migrations et les seeders';

    public function handle(FrenchCompanyDefaults $defaults)
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Cette commande est réservée aux environnements local et testing.');
            return 1;
        }

        if (! User::query()->exists() || ! Company::query()->exists()) {
            $this->error('Aucun utilisateur ou aucune entreprise. Exécutez d’abord php artisan migrate --seed.');
            return 1;
        }

        Company::query()->each(function (Company $company) use ($defaults) {
            $defaults->apply($company);
        });

        Setting::setSetting('profile_complete', 'COMPLETED');
        Storage::disk('local')->put('database_created', 'database_created');

        $this->info('Installation locale AutoFacture finalisée.');
        $this->line('Compte de démonstration : admin@autofacture.local');
        $this->line('Mot de passe : autofacture-dev');

        return 0;
    }
}
