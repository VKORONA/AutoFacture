<?php

use Crater\Security\EncryptedAttribute;

it('chiffre puis déchiffre une valeur sensible', function (): void {
    $service = app(EncryptedAttribute::class);
    $encrypted = $service->encrypt('FR7630006000011234567890189');

    expect($encrypted)
        ->toBeString()
        ->toStartWith('enc:v1:')
        ->not->toContain('FR7630006000011234567890189')
        ->and($service->decrypt($encrypted))->toBe('FR7630006000011234567890189');
});

it('ne chiffre pas deux fois une valeur et accepte les données historiques en clair', function (): void {
    $service = app(EncryptedAttribute::class);
    $encrypted = $service->encrypt('ABCDEFGH');

    expect($service->encrypt($encrypted))->toBe($encrypted)
        ->and($service->decrypt('ancienne-valeur-en-clair'))->toBe('ancienne-valeur-en-clair')
        ->and($service->encrypt(null))->toBeNull();
});
