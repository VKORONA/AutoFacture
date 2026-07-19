<?php

use Crater\Rules\ValidIban;

it('accepte un IBAN français valide avec ou sans espaces', function () {
    $rule = new ValidIban();

    expect($rule->passes('iban', 'FR7630006000011234567890189'))->toBeTrue()
        ->and($rule->passes('iban', 'FR76 3000 6000 0112 3456 7890 189'))->toBeTrue();
});

it('refuse un IBAN dont la somme de contrôle est invalide', function () {
    $rule = new ValidIban();

    expect($rule->passes('iban', 'FR7630006000011234567890188'))->toBeFalse();
});

it('accepte une valeur vide car le champ est facultatif', function () {
    $rule = new ValidIban();

    expect($rule->passes('iban', null))->toBeTrue()
        ->and($rule->passes('iban', ''))->toBeTrue();
});
