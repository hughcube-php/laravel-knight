<?php

namespace HughCube\Laravel\Knight\Tests\Mixin\Database\Query;

use HughCube\Laravel\Knight\Database\Query\Grammars\MySqlGrammar as KnightMySqlGrammar;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use PDO;

class JsonOverlapsMySqlTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $mysqlConfig = $this->resolveMySqlConfig();

        if ($mysqlConfig !== null) {
            $app['config']->set('database.default', 'mysql');
            $app['config']->set('database.connections.mysql', $mysqlConfig);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfMySqlNotConfigured();
        $this->applyMySqlQueryGrammar();
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

    public function testWhereNotJsonOverlaps(): void
    {
        $ids = $this->getQuery()
            ->whereNotJsonOverlaps('tags', ['python'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([1, 3, 4], $this->castIds($ids));
    }

    public function testOrWhereNotJsonOverlaps(): void
    {
        $ids = $this->getQuery()
            ->where('id', 2)
            ->orWhereNotJsonOverlaps('tags', ['php'])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([2, 3, 4], $this->castIds($ids));
    }

    private function getQuery()
    {
        return DB::connection('mysql')->table('knight_json_overlaps_test');
    }

    private function setUpJsonTable(): void
    {
        $connection = DB::connection('mysql');

        $connection->statement('DROP TABLE IF EXISTS knight_json_overlaps_test');
        $connection->statement(
            'CREATE TABLE knight_json_overlaps_test (
                id INT PRIMARY KEY,
                tags JSON NULL,
                meta JSON NULL,
                payload JSON NULL,
                scalar JSON NULL
            )'
        );

        $connection->table('knight_json_overlaps_test')->insert($this->seedRows());
    }

    private function seedRows(): array
    {
        return [
            [
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

    private function resolveMySqlConfig(): ?array
    {
        $testConfig = $this->resolveMySqlConfigFromEnv('TEST_MYSQL');

        if ($testConfig !== null) {
            return $testConfig;
        }

        $dbConnection = getenv('DB_CONNECTION') ?: '';

        if (strtolower($dbConnection) !== 'mysql') {
            return null;
        }

        return $this->resolveMySqlConfigFromEnv('DB');
    }

    private function resolveMySqlConfigFromEnv(string $prefix): ?array
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
            'driver' => 'mysql',
            'host' => $host,
            'port' => ($port === false || $port === '') ? 3306 : (int) $port,
            'database' => $database,
            'username' => $username,
            'password' => ($password === false) ? null : $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ];
    }

    private function isMySqlConfigured(): bool
    {
        return $this->resolveMySqlConfig() !== null;
    }

    private function skipIfMySqlNotConfigured(): void
    {
        if (!$this->isMySqlConfigured()) {
            $this->markTestSkipped('MySQL connection is not configured for JsonOverlapsMySqlTest.');
        }
    }

    private function applyMySqlQueryGrammar(): void
    {
        $connection = DB::connection('mysql');

        if (!$connection->getQueryGrammar() instanceof KnightMySqlGrammar) {
            $connection->setQueryGrammar(new KnightMySqlGrammar($connection));
        }
    }
}
