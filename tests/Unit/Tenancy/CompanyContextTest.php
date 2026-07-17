<?php

use Crater\Tenancy\CompanyContext;

it('mémorise puis libère l’entreprise active', function (): void {
    $context = new CompanyContext();

    expect($context->hasCompany())->toBeFalse()
        ->and($context->idOrNull())->toBeNull();

    $context->set(42);

    expect($context->hasCompany())->toBeTrue()
        ->and($context->id())->toBe(42)
        ->and($context->idOrNull())->toBe(42);

    $context->clear();

    expect($context->hasCompany())->toBeFalse()
        ->and($context->idOrNull())->toBeNull();
});

it('refuse une entreprise invalide ou absente', function (): void {
    $context = new CompanyContext();

    expect(fn () => $context->set(0))->toThrow(\LogicException::class)
        ->and(fn () => $context->id())->toThrow(\LogicException::class);
});
