<?php

namespace Database\Factories;

use Crater\Domain\MicroEntrepreneur\BusinessActivityType;
use Crater\Models\Currency;
use Crater\Models\Item;
use Crater\Models\Unit;
use Crater\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->text,
            'business_activity_type' => BusinessActivityType::SERVICE_BIC->value,
            'company_id' => User::find(1)->companies()->first()->id,
            'price' => $this->faker->randomDigitNotNull,
            'unit_id' => Unit::factory(),
            'creator_id' => User::where('role', 'super admin')->first()->company_id,
            'currency_id' => Currency::find(1)->id,
            'tax_per_item' => $this->faker->randomElement([true, false]),
        ];
    }
}
