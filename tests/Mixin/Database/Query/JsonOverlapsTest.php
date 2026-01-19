<?php

namespace HughCube\Laravel\Knight\Tests\Mixin\Database\Query;

use HughCube\Laravel\Knight\Database\Query\Grammars\PostgresGrammar as KnightPostgresGrammar;
use HughCube\Laravel\Knight\Ide\Database\Query\KIdeBuilder;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
<<<<<<< HEAD
=======
use Illuminate\Database\Query\Builder;
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
use RuntimeException;

class JsonOverlapsTest extends TestCase
{
<<<<<<< HEAD
    private static ?bool $jsonDoesntOverlapSupported = null;
    private static ?bool $orJsonDoesntOverlapSupported = null;

=======
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
    private function makeMySqlConnection(): MySqlConnection
    {
        return new MySqlConnection($this->makePdoResolver(), '', '', ['driver' => 'mysql']);
    }

    private function makePostgresConnection(): PostgresConnection
    {
        return new PostgresConnection($this->makePdoResolver(), '', '', ['driver' => 'pgsql']);
    }

    private function makePdoResolver(): callable
    {
        return function () {
            throw new RuntimeException('PDO connection is not available for SQL-only tests.');
        };
    }

    public function testWhereJsonDoesntOverlapAddsNotClause(): void
    {
        $this->skipIfBuilderJsonDoesntOverlapNotSupported();

        $query = $this->makeMySqlConnection()->table('knight_json_test');
        $query->whereJsonDoesntOverlap('tags', ['php']);

        $this->assertCount(1, $query->wheres);
        $where = $query->wheres[0];

        $this->assertSame('JsonOverlaps', $where['type']);
        $this->assertTrue($where['not']);
        $this->assertSame('and', $where['boolean']);
        $this->assertSame(['["php"]'], $query->getBindings());
    }

    public function testOrWhereJsonDoesntOverlapAddsOrClause(): void
    {
        $this->skipIfBuilderOrWhereJsonDoesntOverlapNotSupported();

        $query = $this->makeMySqlConnection()->table('knight_json_test');
        $query->where('id', 1)->orWhereJsonDoesntOverlap('tags', ['php']);

        $where = $query->wheres[count($query->wheres) - 1];

        $this->assertSame('JsonOverlaps', $where['type']);
        $this->assertTrue($where['not']);
        $this->assertSame('or', $where['boolean']);
    }

    public function testKnightPostgresGrammarCompileJsonOverlapsBuildsJsonbExpression(): void
    {
        $grammar = new KnightPostgresGrammar($this->makePostgresConnection());

        $sql = self::callMethod($grammar, 'compileJsonOverlaps', ['payload->items', '?']);

        $this->assertStringContainsString('jsonb_typeof', $sql);
        $this->assertStringContainsString('jsonb_array_elements', $sql);
        $this->assertStringContainsString('jsonb_each', $sql);
        $this->assertStringContainsString('::jsonb', $sql);
    }

    public function testKIdeBuilderJsonDoesntOverlapHelpersReturnNull(): void
    {
        $builder = new KIdeBuilder();

        $this->assertNull($builder->whereJsonDoesntOverlap('tags', ['php']));
        $this->assertNull($builder->orWhereJsonDoesntOverlap('tags', ['php']));
    }

    private function skipIfBuilderJsonDoesntOverlapNotSupported(): void
    {
<<<<<<< HEAD
        if (! $this->isJsonDoesntOverlapSupported()) {
=======
        if (!method_exists(Builder::class, 'whereJsonDoesntOverlap')
            || !method_exists(Builder::class, 'whereJsonOverlaps')
        ) {
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
            $this->markTestSkipped('Query builder does not support whereJsonDoesntOverlap (Laravel version too old).');
        }
    }

    private function skipIfBuilderOrWhereJsonDoesntOverlapNotSupported(): void
    {
<<<<<<< HEAD
        if (! $this->isOrJsonDoesntOverlapSupported()) {
            $this->markTestSkipped('Query builder does not support orWhereJsonDoesntOverlap (Laravel version too old).');
        }
    }

    private function isJsonDoesntOverlapSupported(): bool
    {
        if (self::$jsonDoesntOverlapSupported !== null) {
            return self::$jsonDoesntOverlapSupported;
        }

        $query = $this->makeMySqlConnection()->table('knight_json_test');

        try {
            $query->whereJsonDoesntOverlap('tags', ['php']);
        } catch (\Throwable $exception) {
            self::$jsonDoesntOverlapSupported = false;
            return false;
        }

        $where = $query->wheres[count($query->wheres) - 1] ?? null;

        self::$jsonDoesntOverlapSupported = ($where['type'] ?? null) === 'JsonOverlaps'
            && ($where['not'] ?? null) === true
            && ($where['boolean'] ?? null) === 'and';

        return self::$jsonDoesntOverlapSupported;
    }

    private function isOrJsonDoesntOverlapSupported(): bool
    {
        if (self::$orJsonDoesntOverlapSupported !== null) {
            return self::$orJsonDoesntOverlapSupported;
        }

        $query = $this->makeMySqlConnection()->table('knight_json_test');

        try {
            $query->orWhereJsonDoesntOverlap('tags', ['php']);
        } catch (\Throwable $exception) {
            self::$orJsonDoesntOverlapSupported = false;
            return false;
        }

        $where = $query->wheres[count($query->wheres) - 1] ?? null;

        self::$orJsonDoesntOverlapSupported = ($where['type'] ?? null) === 'JsonOverlaps'
            && ($where['not'] ?? null) === true
            && ($where['boolean'] ?? null) === 'or';

        return self::$orJsonDoesntOverlapSupported;
    }
=======
        if (!method_exists(Builder::class, 'orWhereJsonDoesntOverlap')) {
            $this->markTestSkipped('Query builder does not support orWhereJsonDoesntOverlap (Laravel version too old).');
        }
    }
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
}
