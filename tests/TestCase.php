<?php

namespace Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use JMac\Testing\Traits\AdditionalAssertions;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use AdditionalAssertions;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            return 'Database\\Factories\\'.Str::afterLast($modelName, '\\').'Factory';
        });
    }

    public function assertDeleted(Model $model): static
    {
        $this->assertModelMissing($model);

        return $this;
    }

    public function assertDatabaseHas($table, array $data = [], $connection = null)
    {
        $tableName = is_string($table) ? $table : (new $table())->getTable();
        $connectionName = $connection ?: config('database.default');
        $integerTypes = ['bigint', 'integer', 'int', 'mediumint', 'smallint', 'tinyint'];

        foreach ($data as $column => $value) {
            if (! is_float($value) || ! Schema::connection($connectionName)->hasColumn($tableName, $column)) {
                continue;
            }

            $columnType = Schema::connection($connectionName)->getColumnType($tableName, $column);

            if (in_array($columnType, $integerTypes, true)) {
                $data[$column] = (int) round($value);
            }
        }

        parent::assertDatabaseHas($table, $data, $connection);

        return $this;
    }
}
