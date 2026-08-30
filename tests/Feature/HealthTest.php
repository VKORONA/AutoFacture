<?php

use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\getJson;

it('reports database and cache readiness without authentication', function () {
    Cache::flush();

    getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database', true)
        ->assertJsonPath('checks.cache', true)
        ->assertJsonMissingPath('environment')
        ->assertJsonMissingPath('database.connection');
});
