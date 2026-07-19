<?php

namespace Database\Factories;

use Crater\Models\Currency;
use Crater\Models\Customer;
use Crater\Models\Payment;
use Crater\Models\PaymentMethod;
use Crater\Models\User;
use Crater\Services\SerialNumberFormatter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition()
    {
        $companyId = User::query()->where('role', 'super admin')->firstOrFail()
            ->companies()->firstOrFail()->id;

        $sequenceNumber = (new SerialNumberFormatter())
            ->setModel(new Payment())
            ->setCompany($companyId)
            ->setNextNumbers();

        return [
            'company_id' => $companyId,
            'payment_date' => $this->faker->date('Y-m-d', 'now'),
            'notes' => $this->faker->text(80),
            'amount' => $this->faker->randomDigitNotNull,
            'sequence_number' => $sequenceNumber->nextSequenceNumber,
            'customer_sequence_number' => $sequenceNumber->nextCustomerSequenceNumber,
            'payment_number' => $sequenceNumber->getNextNumber(),
            'unique_hash' => Str::random(60),
            'payment_method_id' => PaymentMethod::query()
                ->where('company_id', $companyId)
                ->valueOrFail('id'),
            'customer_id' => Customer::factory(),
            'base_amount' => $this->faker->randomDigitNotNull,
            'currency_id' => Currency::query()->valueOrFail('id'),
        ];
    }
}
