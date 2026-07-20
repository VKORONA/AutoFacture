<?php

namespace Database\Seeders;

use Crater\Domain\FrenchInvoicing\FrenchCompanyDefaults;
use Crater\Domain\FrenchInvoicing\FrenchCompanySetup;
use Crater\Models\Company;
use Crater\Models\Country;
use Crater\Models\Currency;
use Crater\Models\Setting;
use Crater\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade;
use Vinkla\Hashids\Facades\Hashids;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $this->stabilizeReferenceIdsForTests();

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
                'password' => app()->environment(['local', 'testing'])
                    ? 'Steph2211'
                    : bin2hex(random_bytes(24)),
            ]);
            $user->save();
        }

        if (app()->environment(['local', 'testing'])) {
            $user->fill([
                'name' => 'Administrateur AutoFacture',
                'role' => 'super admin',
                'password' => 'Steph2211',
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

        if (app()->environment('testing')) {
            $company->forceFill([
                'name' => 'Entreprise AutoFacture Test',
                'siren' => '732829320',
                'siret' => '73282932000074',
                'vat_regime' => 'standard',
                'vat_exempt' => false,
            ])->save();

            $company->address()->updateOrCreate(
                ['company_id' => $company->id],
                [
                    'country_id' => 1,
                    'address_street_1' => '1 rue du Test',
                    'zip' => '75001',
                    'city' => 'Paris',
                ]
            );
        }

        $company->unique_hash = Hashids::connection(Company::class)->encode($company->id);
        $company->save();
        $company->setupDefaultData();

        $euroId = Currency::where('code', 'EUR')->value('id');
        app(FrenchCompanyDefaults::class)->apply($company, $euroId);

        if (app()->environment('testing')) {
            app(FrenchCompanySetup::class)->synchronizeVatDefaults($company->refresh());
        }

        $user->companies()->syncWithoutDetaching([$company->id]);
        BouncerFacade::scope()->to($company->id);
        $user->assign('super admin');

        Setting::setSetting('profile_complete', 0);
    }

    private function stabilizeReferenceIdsForTests(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        if (! Currency::query()->whereKey(1)->exists()) {
            $currencyId = Currency::query()->orderBy('id')->value('id');

            if ($currencyId) {
                DB::table('currencies')->where('id', $currencyId)->update(['id' => 1]);
            }
        }

        if (! Country::query()->whereKey(1)->exists()) {
            $countryId = Country::query()->orderBy('id')->value('id');

            if ($countryId) {
                DB::table('countries')->where('id', $countryId)->update(['id' => 1]);
            }
        }
    }
}
