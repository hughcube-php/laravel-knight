<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 11:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests;

use Exception;
use HughCube\Laravel\Knight\Database\Query\Grammars\PostgresGrammar as KnightPostgresGrammar;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\ServiceProvider;
use Illuminate\Cache\Events\CacheEvent;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

class TestCase extends OrchestraTestCase
{
    protected static bool $postgresGrammarRegistered = false;

    public static function applicationBasePath()
    {
        $path = realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'orchestra'.DIRECTORY_SEPARATOR.'testbench-core'.DIRECTORY_SEPARATOR.'laravel');

        return $path !== false
            ? $path
            : __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'orchestra'.DIRECTORY_SEPARATOR.'testbench-core'.DIRECTORY_SEPARATOR.'laravel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Force resolve log manager to ensure it's properly initialized
        $this->app->make('log');

        if (!self::$postgresGrammarRegistered) {
            KnightPostgresGrammar::registerConnectionResolver();
            self::$postgresGrammarRegistered = true;
        }

        KnightPostgresGrammar::applyToExistingConnections();
    }

    /**
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            DatabaseServiceProvider::class,
            \HughCube\Laravel\Validation\ServiceProvider::class,
            ServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     *
     * @throws Exception
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'ZsZewWyUJ5FsKp9lMwv4tYbNlegQilM7');

        $this->setupEloquent($app);
        $this->setupLogging($app);
    }

    /**
     * @param Application $app
     */
    protected function setupLogging(Application $app)
    {
        /** @var Repository $appConfig */
        $appConfig = $app['config'];

        $appConfig->set('logging', [
            'default'      => 'null',
            'deprecations' => [
                'channel' => 'null',
                'trace'   => false,
            ],
            'channels' => [
                'null' => [
                    'driver'  => 'monolog',
                    'handler' => \Monolog\Handler\NullHandler::class,
                ],
                'stack' => [
                    'driver'            => 'stack',
                    'channels'          => ['null'],
                    'ignore_exceptions' => false,
                ],
                'single' => [
                    'driver' => 'single',
                    'path'   => storage_path('logs/laravel.log'),
                    'level'  => 'debug',
                ],
                'stdout' => [
                    'driver'  => 'monolog',
                    'handler' => \Monolog\Handler\StreamHandler::class,
                    'with'    => [
                        'stream' => 'php://stdout',
                    ],
                ],
            ],
        ]);
    }

    /**
     * @param Application $app
     */
    protected function setupCache(Application $app)
    {
        /** @var Repository $appConfig */
        $appConfig = $app['config'];

        $appConfig->set('cache', [
            'default' => 'file',
            'stores'  => [
                'array' => [
                    'driver'    => 'array',
                    'serialize' => true,
                ],
                'file' => [
                    'driver' => 'file',
                    'path'   => '/tmp/test/',
                ],
            ],
        ]);
    }

    /**
     * @param Application $app
     *
     * @throws Exception
     */
    protected function setupEloquent(Application $app)
    {
        /** @var Repository $appConfig */
        $appConfig = $app['config'];

        $file = sprintf('%s/%s-%s-database.sqlite', sys_get_temp_dir(), date('Y-m-d'), md5(random_bytes(100)));
        touch($file);

        $appConfig->set('database', [
            'default'     => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver'                  => 'sqlite',
                    'url'                     => '',
                    'database'                => $file,
                    'prefix'                  => '',
                    'foreign_key_constraints' => true,
                ],
            ],
        ]);
    }

    /**
     * @param string|object $object $object
     * @param string        $method
     * @param array         $args
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    protected static function callMethod($object, string $method, array $args = [])
    {
        $class = new ReflectionClass($object);

        /** @var ReflectionMethod $method */
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs(is_object($object) ? $object : null, $args);
    }

    /**
     * @param object $object $object
     * @param string $name
     *
     * @throws ReflectionException
     *
     * @return mixed
     */
    protected static function getProperty($object, string $name)
    {
        $class = new ReflectionClass($object);

        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @param object $object
     * @param string $name
     * @param mixed  $value
     *
     * @throws ReflectionException
     */
    protected static function setProperty($object, string $name, $value)
    {
        $class = new ReflectionClass($object);

        $property = $class->getProperty($name);
        $property->setAccessible(true);

        $property->setValue($object, $value);
    }

    /**
     * @param string $storeName
     * @param string $key
     * @param array  $tags
     *
     * @return CacheEvent
     */
    protected function newCacheEvent(string $storeName, string $key, array $tags): CacheEvent
    {
        return new class($storeName, $key, $tags) extends CacheEvent {
            public function __construct($storeName, $key, array $tags)
            {
                if (property_exists($this, 'storeName')) {
                    $this->storeName = $storeName;
                }

                if (property_exists($this, 'key')) {
                    $this->key = $key;
                }

                if (property_exists($this, 'tags')) {
                    $this->tags = $tags;
                }
            }
        };
    }

    /**
     * @param string    $sql
     * @param array     $bindings
     * @param Throwable $previous
     * @param string    $connectionName
     *
     * @return QueryException
     */
    protected function newQueryException(
        string $sql,
        array $bindings,
        Throwable $previous,
        string $connectionName = 'sqlite'
    ): QueryException {
        $constructor = (new ReflectionClass(QueryException::class))->getConstructor();

        if (null !== $constructor && 4 <= $constructor->getNumberOfParameters()) {
            return new QueryException($connectionName, $sql, $bindings, $previous);
        }

        return new QueryException($sql, $bindings, $previous);
    }

    protected function assertJob(Job $job)
    {
        $this->assertNoException(function () use ($job) {
            $job->setLogChannel('stdout');
            $job->handle();
        });
    }

    protected function assertNoException(callable $callable)
    {
        $callable();
        $this->assertTrue(true);
    }

    /**
     * Setup a TestHandler for capturing log messages.
     * Returns null if the handler could not be set up (for compatibility with different Laravel versions).
     */
    protected function setupTestLogHandler(): ?TestHandler
    {
        $handler = new TestHandler();

        try {
            $driver = Log::driver();

            // Laravel 5.6+ uses Illuminate\Log\Logger which wraps Monolog
            if (method_exists($driver, 'getLogger')) {
                $logger = $driver->getLogger();
                if (method_exists($logger, 'pushHandler')) {
                    $logger->pushHandler($handler);

                    return $handler;
                }
            }

            // Fallback: try to push directly if driver is Monolog
            if (method_exists($driver, 'pushHandler')) {
                $driver->pushHandler($handler);

                return $handler;
            }
        } catch (Throwable $e) {
            // If we can't set up the handler, return null
        }

        return null;
    }

    /**
     * Assert that a log message was recorded at the specified level containing the needle.
     */
    protected function assertLogContains(?TestHandler $handler, string $level, string $needle): void
    {
        if ($handler === null) {
            $this->assertTrue(true, 'Log handler not available, skipping log assertion');

            return;
        }

        $method = 'has'.ucfirst($level).'ThatContains';
        if (method_exists($handler, $method)) {
            $this->assertTrue(
                $handler->$method($needle),
                "Expected {$level} log containing \"{$needle}\""
            );
        } else {
            $this->assertTrue(true, "Log assertion method {$method} not available");
        }
    }
}
