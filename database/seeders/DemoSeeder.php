<?php

namespace Database\Seeders;

use Crater\Models\Address;
use Crater\Models\Setting;
use Crater\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run()
    {
        $user = User::whereIs('super admin')->firstOrFail();
        $user->setSettings(['language' => 'fr']);

        $company = $user->companies()->firstOrFail();

        Address::query()->updateOrCreate(
            ['company_id' => $company->id],
            ['country_id' => 1]
        );

        Setting::setSetting('profile_complete', 'COMPLETED');

        \Storage::disk('local')->put('database_created', 'database_created');
    }
}
