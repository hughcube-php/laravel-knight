<?php

namespace HughCube\Laravel\Knight\Tests\Mixin\Database\Query;

use HughCube\Laravel\Knight\Database\Query\Grammars\PostgresGrammar as KnightPostgresGrammar;
use HughCube\Laravel\Knight\Ide\Database\Query\KIdeBuilder;
use HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use RuntimeException;

class JsonOverlapsTest extends TestCase
{
    private function makeMySqlConnection(): MySqlConnection
    {
        return new MySqlConnection($this->makePdoResolver(), '', '', ['driver' => 'mysql']);
    }

    private function makePostgresConnection(): PostgresConnection
    {
        return new PostgresConnection($this->makePdoResolver(), '', '', ['driver' => 'pgsql']);
    }

    private function makeSqliteConnection(): SQLiteConnection
    {
        return new SQLiteConnection($this->makePdoResolver(), '', '', ['driver' => 'sqlite']);
    }

    private function makePdoResolver(): callable
    {
        return function () {
            throw new RuntimeException('PDO connection is not available for SQL-only tests.');
        };
    }

    public function testWhereJsonDoesntOverlapAddsNotClause(): void
    {
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
        $query = $this->makeMySqlConnection()->table('knight_json_test');
        $query->where('id', 1)->orWhereJsonDoesntOverlap('tags', ['php']);

        $where = $query->wheres[count($query->wheres) - 1];

        $this->assertSame('JsonOverlaps', $where['type']);
        $this->assertTrue($where['not']);
        $this->assertSame('or', $where['boolean']);
    }

    public function testWhereNotJsonOverlapsDelegatesToDoesntOverlap(): void
    {
        $query = $this->makeMySqlConnection()->table('knight_json_test');
        $query->whereNotJsonOverlaps('tags', ['php']);

        $where = $query->wheres[0];

        $this->assertSame('JsonOverlaps', $where['type']);
        $this->assertTrue($where['not']);
        $this->assertSame('and', $where['boolean']);
    }

    public function testOrWhereNotJsonOverlapsDelegatesToDoesntOverlap(): void
    {
        $query = $this->makeMySqlConnection()->table('knight_json_test');
        $query->where('id', 1)->orWhereNotJsonOverlaps('tags', ['php']);

        $where = $query->wheres[count($query->wheres) - 1];

        $this->assertSame('JsonOverlaps', $where['type']);
        $this->assertTrue($where['not']);
        $this->assertSame('or', $where['boolean']);
    }

    public function testGrammarMixinCompileJsonOverlapsForMySqlUsesPath(): void
    {
        $grammar = $this->makeMySqlConnection()->getQueryGrammar();
        $mixin = new GrammarMixin();

        $sql = $mixin->compileJsonOverlaps()->call($grammar, 'data->settings->tags', '?');

        $this->assertStringContainsString('json_overlaps', $sql);
        $this->assertStringContainsString('json_extract', $sql);
        $this->assertStringContainsString('$.', $sql);
    }

    public function testGrammarMixinCompileJsonOverlapsForPgsqlUsesJsonbEmulation(): void
    {
        $grammar = $this->makePostgresConnection()->getQueryGrammar();
        $mixin = new GrammarMixin();

        $sql = $mixin->compileJsonOverlaps()->call($grammar, 'payload->items', '?');

        $this->assertStringContainsString('jsonb_typeof', $sql);
        $this->assertStringContainsString('jsonb_array_elements', $sql);
        $this->assertStringContainsString('jsonb_each', $sql);
        $this->assertStringContainsString('::jsonb', $sql);
    }

    public function testGrammarMixinCompileJsonOverlapsThrowsOnUnsupportedDriver(): void
    {
        $grammar = $this->makeSqliteConnection()->getQueryGrammar();
        $mixin = new GrammarMixin();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This database engine does not support JSON overlaps operations.');

        $mixin->compileJsonOverlaps()->call($grammar, 'payload', '?');
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
}
