<?php

namespace Crater\Providers;

use Crater\Tenancy\CompanyContext;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CompanyContext::class, fn (): CompanyContext => new CompanyContext);
    }

    public function boot(CompanyContext $context): void
    {
        $modelClasses = config('tenancy.models', []);

        if (! is_array($modelClasses)) {
            return;
        }

        foreach ($modelClasses as $modelClass) {
            if (! is_string($modelClass) || ! is_a($modelClass, Model::class, true)) {
                continue;
            }

            $modelClass::addGlobalScope('company', function (Builder $builder) use ($context): void {
                if (! $context->hasCompany()) {
                    return;
                }

                $builder->where(
                    $builder->getModel()->qualifyColumn('company_id'),
                    $context->id(),
                );
            });

            $modelClass::creating(function (Model $model) use ($context): void {
                if (! $context->hasCompany()) {
                    return;
                }

                $companyId = $model->getAttribute('company_id');

                if ($companyId === null) {
                    $model->setAttribute('company_id', $context->id());

                    return;
                }

                if ((int) $companyId !== $context->id()) {
                    throw new DomainException('Création interdite en dehors de l’entreprise active.');
                }
            });

            $modelClass::saving(function (Model $model) use ($context): void {
                if (! $context->hasCompany() || ! $model->exists) {
                    return;
                }

                if ((int) $model->getAttribute('company_id') !== $context->id()) {
                    throw new DomainException('Modification interdite en dehors de l’entreprise active.');
                }
            });
        }
    }
}
