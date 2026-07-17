<?php

namespace Database\Seeders;

use Crater\Domain\FrenchInvoicing\FrenchCompanyDefaults;
use Crater\Models\Company;
use Crater\Models\Setting;
use Crater\Models\User;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade;
use Vinkla\Hashids\Facades\Hashids;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $user = User::create([
            'email' => 'admin@autofacture.local',
            'name' => 'Administrateur AutoFacture',
            'role' => 'super admin',
            'password' => 'autofacture-dev',
        ]);

        $company = Company::create([
            'name' => 'Entreprise de démonstration',
            'owner_id' => $user->id,
            'slug' => 'entreprise-demonstration',
        ]);

        $company->unique_hash = Hashids::connection(Company::class)->encode($company->id);
        $company->save();
        $company->setupDefaultData();
        app(FrenchCompanyDefaults::class)->apply($company);
        $user->companies()->attach($company->id);
        BouncerFacade::scope()->to($company->id);
        $user->assign('super admin');

        Setting::setSetting('profile_complete', 0);
    }
}
