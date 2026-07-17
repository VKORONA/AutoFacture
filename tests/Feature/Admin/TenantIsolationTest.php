<?php

use Crater\Models\Company;
use Crater\Models\Currency;
use Crater\Models\Customer;
use Crater\Models\User;
use Crater\Tenancy\CompanyContext;
use DomainException;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
});

it('filtre les données et refuse une écriture pour une autre entreprise', function () {
    $user = User::query()->where('role', 'super admin')->firstOrFail();
    $companyOne = $user->companies()->firstOrFail();
    $companyTwo = Company::query()->create([
        'name' => 'Entreprise isolée',
        'slug' => 'entreprise-isolee',
        'owner_id' => $user->id,
    ]);
    $currencyId = Currency::query()->valueOrFail('id');
    $context = app(CompanyContext::class);

    $customerOne = $context->runWith($companyOne->id, fn () => Customer::query()->create([
        'company_id' => $companyOne->id,
        'creator_id' => $user->id,
        'currency_id' => $currencyId,
        'name' => 'Client entreprise 1',
        'customer_type' => 'business',
    ]));

    $customerTwo = $context->runWith($companyTwo->id, fn () => Customer::query()->create([
        'company_id' => $companyTwo->id,
        'creator_id' => $user->id,
        'currency_id' => $currencyId,
        'name' => 'Client entreprise 2',
        'customer_type' => 'business',
    ]));

    $context->runWith($companyOne->id, function () use ($customerOne, $customerTwo): void {
        expect(Customer::query()->pluck('id')->all())
            ->toContain($customerOne->id)
            ->not->toContain($customerTwo->id)
            ->and(Customer::query()->find($customerTwo->id))->toBeNull();
    });

    expect(fn () => $context->runWith($companyOne->id, fn () => Customer::query()->create([
        'company_id' => $companyTwo->id,
        'creator_id' => $user->id,
        'currency_id' => $currencyId,
        'name' => 'Écriture interdite',
        'customer_type' => 'business',
    ])))->toThrow(DomainException::class);
});
