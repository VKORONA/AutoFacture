<?php

use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);

    Sanctum::actingAs($user, ['*']);
});

test('next number', function () {
    getJson('api/v1/next-number?key=invoice')
        ->assertStatus(200)
        ->assertJson(['nextNumber' => 'FAC-000001']);

    getJson('api/v1/next-number?key=estimate')
        ->assertStatus(200)
        ->assertJson(['nextNumber' => 'DEV-000001']);

    getJson('api/v1/next-number?key=payment')
        ->assertStatus(200)
        ->assertJson(['nextNumber' => 'REG-000001']);
});
