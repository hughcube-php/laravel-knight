<?php

namespace HughCube\Laravel\Knight\Tests\ServiceProvider;

use Closure;
use HughCube\Laravel\Knight\Database\Query\Grammars\PostgresGrammar as KnightPostgresGrammar;
use HughCube\Laravel\Knight\ServiceProvider;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use ReflectionProperty;
use RuntimeException;

class PostgresJsonOverlapsGrammarTest extends TestCase
{
    private array $originalResolvers = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalResolvers = $this->getResolvers();
    }

    protected function tearDown(): void
    {
        $this->setResolvers($this->originalResolvers);

        parent::tearDown();
    }

    public function testRegisterPostgresJsonOverlapsGrammarSetsResolverWithoutExisting(): void
    {
        $this->setPgsqlResolver(null);

        $provider = new ServiceProvider($this->app);
        self::callMethod($provider, 'registerPostgresJsonOverlapsGrammar');

        $resolver = Connection::getResolver('pgsql');
        $this->assertNotNull($resolver);

        $connection = $resolver($this->makePdoResolver(), '', '', ['driver' => 'pgsql']);

        $this->assertInstanceOf(PostgresConnection::class, $connection);
        $this->assertInstanceOf(KnightPostgresGrammar::class, $connection->getQueryGrammar());
    }

    public function testRegisterPostgresJsonOverlapsGrammarWrapsExistingResolver(): void
    {
        $called = false;
        $existingResolver = function ($connection, $database, $prefix, $config) use (&$called) {
            $called = true;

            return new PostgresConnection($connection, $database, $prefix, $config);
        };

        $this->setPgsqlResolver($existingResolver);

        $provider = new ServiceProvider($this->app);
        self::callMethod($provider, 'registerPostgresJsonOverlapsGrammar');

        $resolver = Connection::getResolver('pgsql');
        $connection = $resolver($this->makePdoResolver(), '', '', ['driver' => 'pgsql']);

        $this->assertTrue($called);
        $this->assertInstanceOf(KnightPostgresGrammar::class, $connection->getQueryGrammar());
    }

    public function testApplyPostgresJsonOverlapsGrammarUpdatesExistingConnections(): void
    {
        $pgsqlConnection = $this->makePostgresConnection();
        $mysqlConnection = $this->makeMySqlConnection();

        $db = new class($pgsqlConnection, $mysqlConnection) {
            private array $connections;

            public function __construct(...$connections)
            {
                $this->connections = $connections;
            }

            public function getConnections(): array
            {
                return $this->connections;
            }
        };

        $this->app->instance('db', $db);

        $provider = new ServiceProvider($this->app);
        self::callMethod($provider, 'applyPostgresJsonOverlapsGrammar');

        $this->assertInstanceOf(KnightPostgresGrammar::class, $pgsqlConnection->getQueryGrammar());
        $this->assertNotInstanceOf(KnightPostgresGrammar::class, $mysqlConnection->getQueryGrammar());
    }

    private function makeMySqlConnection(): MySqlConnection
    {
        return new MySqlConnection($this->makePdoResolver(), '', '', ['driver' => 'mysql']);
    }

    private function makePostgresConnection(): PostgresConnection
    {
        return new PostgresConnection($this->makePdoResolver(), '', '', ['driver' => 'pgsql']);
    }

    private function makePdoResolver(): Closure
    {
        return function () {
            throw new RuntimeException('PDO connection is not available for SQL-only tests.');
        };
    }

    private function getResolvers(): array
    {
        $property = new ReflectionProperty(Connection::class, 'resolvers');
        $property->setAccessible(true);

        $resolvers = $property->getValue();

        return is_array($resolvers) ? $resolvers : [];
    }

    private function setResolvers(array $resolvers): void
    {
        $property = new ReflectionProperty(Connection::class, 'resolvers');
        $property->setAccessible(true);
        $property->setValue(null, $resolvers);
    }

    private function setPgsqlResolver(?Closure $resolver): void
    {
        $resolvers = $this->getResolvers();

        if ($resolver === null) {
            unset($resolvers['pgsql']);
        } else {
            $resolvers['pgsql'] = $resolver;
        }

        $this->setResolvers($resolvers);
    }
}
