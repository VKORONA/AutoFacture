<?php

namespace Database\Seeders;

use Crater\Domain\FrenchInvoicing\FrenchCompanyDefaults;
use Crater\Models\Company;
use Crater\Models\Currency;
use Crater\Models\Setting;
use Crater\Models\User;
use Illuminate\Database\Seeder;
use Silber\Bouncer\BouncerFacade;
use Vinkla\Hashids\Facades\Hashids;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $user = User::query()->where('email', 'admin@autofacture.local')->first();

        if (! $user) {
            $user = new User();

            if (app()->environment('testing')) {
                $user->id = 1;
            }

            $user->fill([
                'email' => 'admin@autofacture.local',
                'name' => 'Administrateur AutoFacture',
                'role' => 'super admin',
                'password' => 'autofacture-dev',
            ]);
            $user->save();
        }

        $company = Company::query()->where('slug', 'entreprise-demonstration')->first();

        if (! $company) {
            $company = new Company();

            if (app()->environment('testing')) {
                $company->id = 1;
            }

            $company->fill([
                'name' => 'Entreprise de démonstration',
                'owner_id' => $user->id,
                'slug' => 'entreprise-demonstration',
            ]);
            $company->save();
        }

        $company->unique_hash = Hashids::connection(Company::class)->encode($company->id);
        $company->save();
        $company->setupDefaultData();

        $euroId = Currency::where('code', 'EUR')->value('id');
        app(FrenchCompanyDefaults::class)->apply($company, $euroId);

        $user->companies()->syncWithoutDetaching([$company->id]);
        BouncerFacade::scope()->to($company->id);
        $user->assign('super admin');

        Setting::setSetting('profile_complete', 0);
    }
}
