<?php

namespace Crater\Console\Commands;

use Crater\Models\Company;
use Crater\Models\FileDisk;
use Illuminate\Console\Command;

class EncryptSensitiveData extends Command
{
    protected $signature = 'autofacture:encrypt-sensitive-data {--dry-run : Compter sans modifier}';

    protected $description = 'Chiffre les IBAN, BIC et secrets de stockage encore enregistrés en clair';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $companies = 0;
        $disks = 0;

        Company::query()->orderBy('id')->chunkById(100, function ($items) use ($dryRun, &$companies): void {
            foreach ($items as $company) {
                if ($company->iban || $company->bic) {
                    $companies++;

                    if (! $dryRun) {
                        $company->save();
                    }
                }
            }
        });

        FileDisk::query()->orderBy('id')->chunkById(100, function ($items) use ($dryRun, &$disks): void {
            foreach ($items as $disk) {
                if ($disk->credentials) {
                    $disks++;

                    if (! $dryRun) {
                        $disk->save();
                    }
                }
            }
        });

        $mode = $dryRun ? 'à chiffrer' : 'chiffrés';
        $this->info("{$companies} entreprise(s) et {$disks} stockage(s) {$mode}.");

        return self::SUCCESS;
    }
}
