<?php

namespace Pest\Faker;

use Faker\Factory;
use Faker\Generator;

if (! function_exists(__NAMESPACE__.'\\faker')) {
    function faker(?string $locale = null): Generator
    {
        static $instances = [];

        $locale ??= $_ENV['APP_FAKER_LOCALE'] ?? $_SERVER['APP_FAKER_LOCALE'] ?? 'en_US';

        return $instances[$locale] ??= Factory::create($locale);
    }
}
