<?php

use Crater\Rules\ValidFrenchBusinessNumber;

it('accepte un SIREN valide', function () {
    $rule = new ValidFrenchBusinessNumber(9, 'SIREN');

    expect($rule->passes('siren', '732829320'))->toBeTrue();
});

it('refuse un SIREN invalide', function () {
    $rule = new ValidFrenchBusinessNumber(9, 'SIREN');

    expect($rule->passes('siren', '732829321'))->toBeFalse();
});

it('accepte un SIRET valide', function () {
    $rule = new ValidFrenchBusinessNumber(14, 'SIRET');

    expect($rule->passes('siret', '73282932000074'))->toBeTrue();
});

it('refuse un SIRET invalide', function () {
    $rule = new ValidFrenchBusinessNumber(14, 'SIRET');

    expect($rule->passes('siret', '73282932000075'))->toBeFalse();
});

it('accepte une valeur vide car le champ est facultatif', function () {
    $rule = new ValidFrenchBusinessNumber(9, 'SIREN');

    expect($rule->passes('siren', null))->toBeTrue()
        ->and($rule->passes('siren', ''))->toBeTrue();
});
