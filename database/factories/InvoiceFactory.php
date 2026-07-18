<?php

namespace Database\Factories;

use Crater\Models\Currency;
use Crater\Models\Customer;
use Crater\Models\Invoice;
use Crater\Models\User;
use Crater\Services\SerialNumberFormatter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function sent()
    {
        return $this->state(fn () => ['status' => Invoice::STATUS_SENT]);
    }

    public function viewed()
    {
        return $this->state(fn () => ['status' => Invoice::STATUS_VIEWED]);
    }

    public function completed()
    {
        return $this->state(fn () => ['status' => Invoice::STATUS_COMPLETED]);
    }

    public function unpaid()
    {
        return $this->state(fn () => ['status' => Invoice::STATUS_UNPAID]);
    }

    public function partiallyPaid()
    {
        return $this->state(fn () => ['status' => Invoice::STATUS_PARTIALLY_PAID]);
    }

    public function paid()
    {
        return $this->state(fn () => ['status' => Invoice::STATUS_PAID]);
    }

    public function definition(): array
    {
        $user = User::query()->where('role', 'super admin')->firstOrFail();
        $companyId = $user->companies()->firstOrFail()->id;
        $sequence = (new SerialNumberFormatter())
            ->setModel(new Invoice())
            ->setCompany($companyId)
            ->setNextNumbers();
        $total = $this->faker->numberBetween(100, 10000);
        $subTotal = $this->faker->numberBetween($total, $total + 2000);
        $tax = max(0, $total - $subTotal);
        $exchangeRate = $this->faker->randomFloat(6, 0.1, 100);

        return [
            'creator_id' => $user->id,
            'invoice_date' => $this->faker->date('Y-m-d', 'now'),
            'due_date' => $this->faker->date('Y-m-d', '+30 days'),
            'invoice_number' => $sequence->getNextNumber(),
            'sequence_number' => $sequence->nextSequenceNumber,
            'customer_sequence_number' => $sequence->nextCustomerSequenceNumber,
            'reference_number' => $sequence->getNextNumber(),
            'template_name' => 'invoice1',
            'status' => Invoice::STATUS_DRAFT,
            'tax_per_item' => 'NO',
            'discount_per_item' => 'NO',
            'paid_status' => Invoice::STATUS_UNPAID,
            'company_id' => $companyId,
            'sub_total' => $subTotal,
            'total' => $total,
            'discount_type' => 'fixed',
            'discount_val' => 0,
            'discount' => 0,
            'tax' => $tax,
            'due_amount' => $total,
            'notes' => $this->faker->text(80),
            'unique_hash' => Str::random(60),
            'customer_id' => Customer::factory(),
            'recurring_invoice_id' => null,
            'exchange_rate' => $exchangeRate,
            'base_discount_val' => 0,
            'base_sub_total' => (int) round($subTotal * $exchangeRate),
            'base_total' => (int) round($total * $exchangeRate),
            'base_tax' => (int) round($tax * $exchangeRate),
            'base_due_amount' => (int) round($total * $exchangeRate),
            'currency_id' => Currency::query()->valueOrFail('id'),
        ];
    }
}
