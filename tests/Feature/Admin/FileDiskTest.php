<?php

use Crater\Models\FileDisk;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);
    Sanctum::actingAs($user, ['*']);
});

test('get file disks', function () {
    getJson('/api/v1/disks')->assertOk();
});

test('create file disk with encrypted credentials', function () {
    $payload = FileDisk::factory()->raw();

    postJson('/api/v1/disks', $payload)->assertSuccessful();

    $disk = FileDisk::query()->where('name', $payload['name'])->firstOrFail();
    $rawCredentials = DB::table('file_disks')->where('id', $disk->id)->value('credentials');

    expect($rawCredentials)
        ->toBeString()
        ->toStartWith('enc:v1:')
        ->not->toContain((string) ($payload['credentials']['root'] ?? ''))
        ->and(json_decode($disk->credentials, true))->toMatchArray($payload['credentials']);
});

test('update file disk with encrypted credentials', function () {
    $disk = FileDisk::factory()->create();
    $payload = FileDisk::factory()->raw();

    putJson("/api/v1/disks/{$disk->id}", $payload)->assertOk();

    $disk->refresh();
    $rawCredentials = DB::table('file_disks')->where('id', $disk->id)->value('credentials');

    expect($disk->name)->toBe($payload['name'])
        ->and($disk->driver)->toBe($payload['driver'])
        ->and($rawCredentials)->toStartWith('enc:v1:')
        ->and(json_decode($disk->credentials, true))->toMatchArray($payload['credentials']);
});

test('get disk', function () {
    $disk = FileDisk::factory()->create();

    getJson("/api/v1/disks/{$disk->driver}")->assertOk();
});

test('get drivers', function () {
    getJson('/api/v1/disk/drivers')->assertOk();
});
