<?php

namespace Crater\Console\Commands;

use Crater\Models\User;
use Illuminate\Console\Command;

class ConfigureLocalAdminCredentials extends Command
{
    protected $signature = 'autofacture:credentials-local';

    protected $description = 'Configure les identifiants du compte administrateur local AutoFacture.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Cette commande est réservée aux environnements local et testing.');

            return self::FAILURE;
        }

        $user = User::query()
            ->where('email', 'admin@autofacture.local')
            ->first();

        if (! $user) {
            $this->error('Le compte administrateur local est introuvable. Exécutez d’abord les seeders AutoFacture.');

            return self::FAILURE;
        }

        $user->forceFill([
            'name' => 'Administrateur AutoFacture',
            'role' => 'super admin',
        ]);
        $user->password = 'Steph2211';
        $user->save();
        $user->tokens()->delete();

        $this->info('Identifiant local : admin');
        $this->info('Mot de passe local : Steph2211');

        return self::SUCCESS;
    }
}
