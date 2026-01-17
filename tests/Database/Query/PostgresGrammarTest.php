<?php

namespace HughCube\Laravel\Knight\Tests\Database\Query;

use HughCube\Laravel\Knight\Database\Query\Grammars\PostgresGrammar;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\PostgresConnection;
use RuntimeException;

class PostgresGrammarTest extends TestCase
{
    public function testCompileJsonOverlapsBuildsSql()
    {
        $grammar = new PostgresGrammar($this->makePostgresConnection());

        $sql = self::callMethod($grammar, 'compileJsonOverlaps', ['payload->settings->tags', '?']);

        $this->assertStringContainsString('jsonb_typeof', $sql);
        $this->assertStringContainsString('::jsonb', $sql);
        $this->assertStringContainsString('"payload"', $sql);
        $this->assertStringContainsString('jsonb_array_elements', $sql);
    }

    private function makePostgresConnection(): PostgresConnection
    {
        return new PostgresConnection(function () {
            throw new RuntimeException('PDO is not required for grammar-only tests.');
        }, '', '', ['driver' => 'pgsql']);
    }
}
