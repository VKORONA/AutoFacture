<?php

namespace Database\Factories;

use Crater\Models\Currency;
use Crater\Models\Customer;
use Crater\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $companyId = User::query()->where('role', 'super admin')->firstOrFail()
            ->companies()->firstOrFail()->id;

        return [
            'name' => $this->faker->name,
            'company_name' => $this->faker->company,
            'contact_name' => $this->faker->name,
            'prefix' => (string) $this->faker->randomDigitNotNull,
            'website' => $this->faker->url,
            'enable_portal' => true,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'company_id' => $companyId,
            'creator_id' => User::query()->where('role', 'super admin')->valueOrFail('id'),
            'password' => Hash::make('secret'),
            'currency_id' => Currency::query()->valueOrFail('id'),
            'customer_type' => 'business',
        ];
    }
}
