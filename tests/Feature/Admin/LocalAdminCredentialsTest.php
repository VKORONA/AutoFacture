<?php

use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
});

it('connects to the local web application with admin and the configured password', function () {
    $user = User::query()->where('email', 'admin@autofacture.local')->firstOrFail();

    postJson('/login', [
        'email' => 'admin',
        'password' => 'Steph2211',
        'remember' => false,
    ])->assertNoContent();

    assertAuthenticatedAs($user);
});

it('connects to the local API with the same short identifier', function () {
    postJson('/api/v1/auth/login', [
        'username' => 'admin',
        'password' => 'Steph2211',
        'device_name' => 'AutoFacture Test',
    ])
        ->assertOk()
        ->assertJsonPath('type', 'Bearer')
        ->assertJsonStructure(['token']);
});

it('updates an existing local administrator password through the artisan command', function () {
    $user = User::query()->where('email', 'admin@autofacture.local')->firstOrFail();
    $user->password = 'ancien-mot-de-passe';
    $user->save();

    expect(Hash::check('Steph2211', $user->fresh()->password))->toBeFalse();

    expect(Artisan::call('autofacture:credentials-local'))->toBe(0)
        ->and(Hash::check('Steph2211', $user->fresh()->password))->toBeTrue();
});
