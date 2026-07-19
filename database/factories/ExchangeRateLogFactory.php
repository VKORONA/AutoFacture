<?php

namespace Database\Factories;

use Crater\Models\Currency;
use Crater\Models\ExchangeRateLog;
use Crater\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeRateLogFactory extends Factory
{
    protected $model = ExchangeRateLog::class;

    public function definition()
    {
        $companyId = User::query()->where('role', 'super admin')->firstOrFail()
            ->companies()->firstOrFail()->id;
        $currencies = Currency::query()->orderBy('id')->limit(2)->pluck('id');

        return [
            'company_id' => $companyId,
            'base_currency_id' => $currencies->first(),
            'currency_id' => $currencies->last(),
            'exchange_rate' => $this->faker->randomFloat(4, 0.1, 10),
        ];
    }
}
