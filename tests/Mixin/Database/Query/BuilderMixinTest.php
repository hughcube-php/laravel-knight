<?php

namespace HughCube\Laravel\Knight\Tests\Mixin\Database\Query;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Query\Builder;
use RuntimeException;

class BuilderMixinTest extends TestCase
{
    public function testArrayMethodsWithCollection(): void
    {
        foreach ($this->getArrayMethods() as $data) {
            [$method, $expectedOperator, $expectedType] = $data;

            $builder = $this->makeBuilder();
            $collection = collect([1, 2, 3]);

            $builder->$method('tags', $collection);

            $this->assertCount(1, $builder->wheres, "Failed for method: {$method}");
            $this->assertEquals($expectedType, $builder->wheres[0]['type'], "Failed for method: {$method}");
            $this->assertEquals('tags', $builder->wheres[0]['column'], "Failed for method: {$method}");
            $this->assertEquals([1, 2, 3], $builder->wheres[0]['value'], "Failed for method: {$method}");
            $this->assertCount(3, $builder->getBindings(), "Failed for method: {$method}");
        }
    }

    public function testArrayMethodsWithArray(): void
    {
        foreach ($this->getArrayMethods() as $data) {
            [$method, $expectedOperator, $expectedType] = $data;

            $builder = $this->makeBuilder();
            $array = [4, 5, 6];

            $builder->$method('tags', $array);

            $this->assertCount(1, $builder->wheres, "Failed for method: {$method}");
            $this->assertEquals($expectedType, $builder->wheres[0]['type'], "Failed for method: {$method}");
            $this->assertEquals('tags', $builder->wheres[0]['column'], "Failed for method: {$method}");
            $this->assertEquals([4, 5, 6], $builder->wheres[0]['value'], "Failed for method: {$method}");
            $this->assertCount(3, $builder->getBindings(), "Failed for method: {$method}");
        }
    }

    public function testArrayMethodsWithEmptyCollection(): void
    {
        foreach ($this->getArrayMethods() as $data) {
            [$method, $expectedOperator, $expectedType] = $data;

            $builder = $this->makeBuilder();
            $collection = collect([]);

            $builder->$method('tags', $collection);

            $this->assertCount(1, $builder->wheres, "Failed for method: {$method}");
            $this->assertEquals($expectedType, $builder->wheres[0]['type'], "Failed for method: {$method}");
            $this->assertEquals([], $builder->wheres[0]['value'], "Failed for method: {$method}");
            $this->assertCount(0, $builder->getBindings(), "Failed for method: {$method}");
        }
    }

    public function testWhereIntArrayContainsWithCollectionGeneratesCorrectSql(): void
    {
        $builder = $this->makeBuilder();
        $collection = collect([1, 2, 3]);

        $builder->whereIntArrayContains('tags', $collection);

        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        $this->assertStringContainsString('@>', $sql);
        $this->assertStringContainsString('ARRAY[?, ?, ?]::integer[]', $sql);
        $this->assertEquals([1, 2, 3], $bindings);
    }

    public function testWhereTextArrayContainsWithCollectionGeneratesCorrectSql(): void
    {
        $builder = $this->makeBuilder();
        $collection = collect(['php', 'laravel']);

        $builder->whereTextArrayContains('tags', $collection);

        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        $this->assertStringContainsString('@>', $sql);
        $this->assertStringContainsString('ARRAY[?, ?]::text[]', $sql);
        $this->assertEquals(['php', 'laravel'], $bindings);
    }

    public function testBindingCountMatchesPlaceholderCountWithCollection(): void
    {
        $builder = $this->makeBuilder();
        $collection = collect([1, 2, 3, 4, 5]);

        $builder->whereIntArrayContains('tags', $collection);

        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        $placeholderCount = substr_count($sql, '?');
        $this->assertEquals($placeholderCount, count($bindings));
    }

    public function testBindingCountMatchesPlaceholderCountWithNestedCollection(): void
    {
        $builder = $this->makeBuilder();
        $collection = collect([10, 20, 30]);

        $builder->whereIntArrayOverlaps('ids', $collection);

        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        $placeholderCount = substr_count($sql, '?');
        $this->assertEquals($placeholderCount, count($bindings));
        $this->assertEquals([10, 20, 30], $bindings);
    }

    public function testMultipleWhereClausesWithCollections(): void
    {
        $builder = $this->makeBuilder();

        $builder->whereIntArrayContains('tags', collect([1, 2]))
            ->whereTextArrayOverlaps('labels', collect(['a', 'b', 'c']));

        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        $placeholderCount = substr_count($sql, '?');
        $this->assertEquals($placeholderCount, count($bindings));
        $this->assertEquals([1, 2, 'a', 'b', 'c'], $bindings);
    }

    public function testOrWhereWithCollection(): void
    {
        $builder = $this->makeBuilder();

        $builder->whereIntArrayContains('tags', [1])
            ->orWhereIntArrayContains('other_tags', collect([2, 3]));

        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        $this->assertStringContainsString(' or ', strtolower($sql));
        $this->assertEquals([1, 2, 3], $bindings);
    }

    public function testWhereNotWithCollection(): void
    {
        $builder = $this->makeBuilder();

        $builder->whereNotIntArrayContains('tags', collect([1, 2]));

        $sql = $builder->toSql();
        $bindings = $builder->getBindings();

        $this->assertStringContainsString('not', strtolower($sql));
        $this->assertEquals([1, 2], $bindings);
    }

    protected function getArrayMethods(): array
    {
        return [
            // Integer array methods
            ['whereIntArrayContains', '@>', 'IntArrayContains'],
            ['whereIntArrayContainedBy', '<@', 'IntArrayContainedBy'],
            ['whereIntArrayOverlaps', '&&', 'IntArrayOverlaps'],

            // BigInt array methods
            ['whereBigIntArrayContains', '@>', 'BigIntArrayContains'],
            ['whereBigIntArrayContainedBy', '<@', 'BigIntArrayContainedBy'],
            ['whereBigIntArrayOverlaps', '&&', 'BigIntArrayOverlaps'],

            // SmallInt array methods
            ['whereSmallIntArrayContains', '@>', 'SmallIntArrayContains'],
            ['whereSmallIntArrayContainedBy', '<@', 'SmallIntArrayContainedBy'],
            ['whereSmallIntArrayOverlaps', '&&', 'SmallIntArrayOverlaps'],

            // Text array methods
            ['whereTextArrayContains', '@>', 'TextArrayContains'],
            ['whereTextArrayContainedBy', '<@', 'TextArrayContainedBy'],
            ['whereTextArrayOverlaps', '&&', 'TextArrayOverlaps'],

            // Boolean array methods
            ['whereBooleanArrayContains', '@>', 'BooleanArrayContains'],
            ['whereBooleanArrayContainedBy', '<@', 'BooleanArrayContainedBy'],
            ['whereBooleanArrayOverlaps', '&&', 'BooleanArrayOverlaps'],

            // Double array methods
            ['whereDoubleArrayContains', '@>', 'DoubleArrayContains'],
            ['whereDoubleArrayContainedBy', '<@', 'DoubleArrayContainedBy'],
            ['whereDoubleArrayOverlaps', '&&', 'DoubleArrayOverlaps'],

            // Float array methods
            ['whereFloatArrayContains', '@>', 'FloatArrayContains'],
            ['whereFloatArrayContainedBy', '<@', 'FloatArrayContainedBy'],
            ['whereFloatArrayOverlaps', '&&', 'FloatArrayOverlaps'],

            // UUID array methods
            ['whereUuidArrayContains', '@>', 'UuidArrayContains'],
            ['whereUuidArrayContainedBy', '<@', 'UuidArrayContainedBy'],
            ['whereUuidArrayOverlaps', '&&', 'UuidArrayOverlaps'],
        ];
    }

    private function makeBuilder(): Builder
    {
        $connection = $this->makePostgresConnection();

        return new Builder($connection, $connection->getQueryGrammar(), $connection->getPostProcessor());
    }

    private function makePostgresConnection(): PostgresConnection
    {
        return new PostgresConnection(function () {
            throw new RuntimeException('PDO is not required for builder-only tests.');
        }, '', '', ['driver' => 'pgsql']);
    }
}
