<?php

namespace HughCube\Laravel\Knight\Tests\Mixin\Database\Query;

use HughCube\Laravel\Knight\Database\Query\Grammars\PostgresGrammar as KnightPostgresGrammar;
use HughCube\Laravel\Knight\Tests\TestCase;
<<<<<<< HEAD
=======
use Illuminate\Database\Query\Builder;
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
use Illuminate\Support\Facades\DB;
use PDO;

class JsonOverlapsPgsqlTest extends TestCase
{
<<<<<<< HEAD
    private static ?bool $jsonOverlapsBuilderSupported = null;

=======
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $pgsqlConfig = $this->resolvePgsqlConfig();

        if ($pgsqlConfig !== null) {
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql', $pgsqlConfig);
        }
    }

    protected function setUp(): void
    {
        // 按需注册 PostgreSQL JSON Overlaps 支持
        KnightPostgresGrammar::registerConnectionResolver();
<<<<<<< HEAD
        #KnightPostgresGrammar::applyToExistingConnections();
=======
        //KnightPostgresGrammar::applyToExistingConnections();
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f

        parent::setUp();

        $this->skipIfPgsqlNotConfigured();
        $this->skipIfBuilderJsonOverlapsNotSupported();

        $this->setUpJsonTable();
    }

    public function testWhereJsonOverlapsWithArray(): void
    {
        $ids = $this->getQuery()
            ->whereJsonOverlaps('tags', ['php'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1], $this->castIds($ids));
    }

    public function testWhereJsonOverlapsWithObject(): void
    {
        $ids = $this->getQuery()
            ->whereJsonOverlaps('meta', ['role' => 'admin'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1, 3], $this->castIds($ids));
    }

    public function testWhereJsonOverlapsWithScalar(): void
    {
        $ids = $this->getQuery()
            ->whereJsonOverlaps('scalar', 'php')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1], $this->castIds($ids));
    }

    public function testWhereJsonOverlapsWithPath(): void
    {
        $ids = $this->getQuery()
            ->whereJsonOverlaps('payload->settings->tags', ['php'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1, 3], $this->castIds($ids));
    }

    public function testWhereJsonDoesntOverlap(): void
    {
        $ids = $this->getQuery()
            ->whereJsonDoesntOverlap('tags', ['php'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([2, 3, 4], $this->castIds($ids));
    }

    public function testWhereJsonDoesntOverlapWithPath(): void
    {
        $ids = $this->getQuery()
            ->whereJsonDoesntOverlap('payload->settings->tags', ['php'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([2, 4], $this->castIds($ids));
    }

    public function testOrWhereJsonOverlaps(): void
    {
        $ids = $this->getQuery()
            ->where('id', 2)
            ->orWhereJsonOverlaps('tags', ['php'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1, 2], $this->castIds($ids));
    }

    public function testOrWhereJsonDoesntOverlap(): void
    {
        $ids = $this->getQuery()
            ->where('id', 2)
            ->orWhereJsonDoesntOverlap('tags', ['php'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([2, 3, 4], $this->castIds($ids));
    }

    private function getQuery()
    {
        $query = DB::connection('pgsql')->table('knight_json_overlaps_test');

        if (method_exists($query, 'useWritePdo')) {
            $query->useWritePdo();
        }

        return $query;
    }

    private function setUpJsonTable(): void
    {
        $connection = DB::connection('pgsql');

        try {
            $connection->statement('DROP TABLE IF EXISTS knight_json_overlaps_test');
            $connection->statement(
                'CREATE TABLE knight_json_overlaps_test (
                    id INTEGER PRIMARY KEY,
                    tags JSONB NULL,
                    meta JSONB NULL,
                    payload JSONB NULL,
                    scalar JSONB NULL
                )'
            );

            $connection->table('knight_json_overlaps_test')->insert($this->seedRows());
        } catch (\Throwable $exception) {
            $this->markTestSkipped('PostgreSQL JSON overlaps setup failed: '.$exception->getMessage());
        }
    }

    private function seedRows(): array
    {
        return [
            [
<<<<<<< HEAD
                'id' => 1,
                'tags' => $this->encodeJson(['php', 'laravel']),
                'meta' => $this->encodeJson(['role' => 'admin', 'team' => 'alpha']),
                'payload' => $this->encodeJson(['settings' => ['tags' => ['php', 'mysql']], 'status' => 'active']),
                'scalar' => $this->encodeJson('php'),
            ],
            [
                'id' => 2,
                'tags' => $this->encodeJson(['python', 'django']),
                'meta' => $this->encodeJson(['role' => 'user', 'team' => 'beta']),
                'payload' => $this->encodeJson(['settings' => ['tags' => ['python']], 'status' => 'inactive']),
                'scalar' => $this->encodeJson('python'),
            ],
            [
                'id' => 3,
                'tags' => $this->encodeJson(['go', 'rust']),
                'meta' => $this->encodeJson(['role' => 'admin', 'team' => 'beta', 'flag' => true]),
                'payload' => $this->encodeJson(['settings' => ['tags' => ['rust', 'php']], 'status' => 'active']),
                'scalar' => $this->encodeJson('go'),
            ],
            [
                'id' => 4,
                'tags' => $this->encodeJson([]),
                'meta' => $this->encodeJson(['role' => 'guest']),
                'payload' => $this->encodeJson(['settings' => ['tags' => []], 'status' => 'disabled']),
                'scalar' => $this->encodeJson(null),
=======
                'id'      => 1,
                'tags'    => $this->encodeJson(['php', 'laravel']),
                'meta'    => $this->encodeJson(['role' => 'admin', 'team' => 'alpha']),
                'payload' => $this->encodeJson(['settings' => ['tags' => ['php', 'mysql']], 'status' => 'active']),
                'scalar'  => $this->encodeJson('php'),
            ],
            [
                'id'      => 2,
                'tags'    => $this->encodeJson(['python', 'django']),
                'meta'    => $this->encodeJson(['role' => 'user', 'team' => 'beta']),
                'payload' => $this->encodeJson(['settings' => ['tags' => ['python']], 'status' => 'inactive']),
                'scalar'  => $this->encodeJson('python'),
            ],
            [
                'id'      => 3,
                'tags'    => $this->encodeJson(['go', 'rust']),
                'meta'    => $this->encodeJson(['role' => 'admin', 'team' => 'beta', 'flag' => true]),
                'payload' => $this->encodeJson(['settings' => ['tags' => ['rust', 'php']], 'status' => 'active']),
                'scalar'  => $this->encodeJson('go'),
            ],
            [
                'id'      => 4,
                'tags'    => $this->encodeJson([]),
                'meta'    => $this->encodeJson(['role' => 'guest']),
                'payload' => $this->encodeJson(['settings' => ['tags' => []], 'status' => 'disabled']),
                'scalar'  => $this->encodeJson(null),
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
            ],
        ];
    }

    private function encodeJson($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    private function castIds(array $ids): array
    {
        return array_map('intval', $ids);
    }

    private function resolvePgsqlConfig(): ?array
    {
        $testConfig = $this->resolvePgsqlConfigFromEnv('TEST_PGSQL');

        if ($testConfig !== null) {
            return $testConfig;
        }

        $dbConnection = getenv('DB_CONNECTION') ?: '';

        if (strtolower($dbConnection) !== 'pgsql') {
            return null;
        }

        return $this->resolvePgsqlConfigFromEnv('DB');
    }

    private function resolvePgsqlConfigFromEnv(string $prefix): ?array
    {
        $host = getenv($prefix.'_HOST');
        $database = getenv($prefix.'_DATABASE');
        $username = getenv($prefix.'_USERNAME');

        if ($host === false || $host === '' || $database === false || $database === '' || $username === false || $username === '') {
            return null;
        }

        $port = getenv($prefix.'_PORT');
        $password = getenv($prefix.'_PASSWORD');

        return [
<<<<<<< HEAD
            'driver' => 'pgsql',
            'host' => $host,
            'port' => ($port === false || $port === '') ? 5432 : (int) $port,
            'database' => $database,
            'username' => $username,
            'password' => ($password === false) ? null : $password,
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'options' => [
                PDO::ATTR_PERSISTENT => true,
=======
            'driver'   => 'pgsql',
            'host'     => $host,
            'port'     => ($port === false || $port === '') ? 5432 : (int) $port,
            'database' => $database,
            'username' => $username,
            'password' => ($password === false) ? null : $password,
            'charset'  => 'utf8',
            'prefix'   => '',
            'schema'   => 'public',
            'options'  => [
                PDO::ATTR_PERSISTENT       => true,
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ];
    }

    private function isPgsqlConfigured(): bool
    {
        return $this->resolvePgsqlConfig() !== null;
    }

    private function skipIfPgsqlNotConfigured(): void
    {
        if (!$this->isPgsqlConfigured()) {
            $this->markTestSkipped('PostgreSQL connection is not configured for JsonOverlapsPgsqlTest.');
        }
    }

    private function skipIfBuilderJsonOverlapsNotSupported(): void
    {
<<<<<<< HEAD
        if (!$this->isJsonOverlapsBuilderSupported()) {
            $this->markTestSkipped('Query builder does not support JSON overlaps methods (Laravel version too old).');
        }
    }

    private function isJsonOverlapsBuilderSupported(): bool
    {
        if (self::$jsonOverlapsBuilderSupported !== null) {
            return self::$jsonOverlapsBuilderSupported;
        }

        $makeQuery = function () {
            return DB::connection('pgsql')->table('knight_json_overlaps_test');
        };

        $supported = $this->probeJsonOverlapsWhere($makeQuery, 'whereJsonOverlaps', false, 'and')
            && $this->probeJsonOverlapsWhere($makeQuery, 'whereJsonDoesntOverlap', true, 'and')
            && $this->probeJsonOverlapsWhere($makeQuery, 'orWhereJsonOverlaps', false, 'or')
            && $this->probeJsonOverlapsWhere($makeQuery, 'orWhereJsonDoesntOverlap', true, 'or');

        self::$jsonOverlapsBuilderSupported = $supported;

        return $supported;
    }

    private function probeJsonOverlapsWhere(callable $makeQuery, string $method, bool $not, string $boolean): bool
    {
        $query = $makeQuery();

        try {
            $query->$method('tags', ['php']);
        } catch (\Throwable $exception) {
            return false;
        }

        $where = $query->wheres[count($query->wheres) - 1] ?? null;

        return ($where['type'] ?? null) === 'JsonOverlaps'
            && ($where['not'] ?? null) === $not
            && ($where['boolean'] ?? null) === $boolean;
    }
=======
        if (!method_exists(Builder::class, 'whereJsonOverlaps')) {
            $this->markTestSkipped('Query builder does not support whereJsonOverlaps (Laravel version too old).');
        }
    }
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
}
