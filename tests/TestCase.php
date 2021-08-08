<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 11:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class TestCase extends OrchestraTestCase
{
    /**
     * @param  Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            DatabaseServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     * @throws Exception
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'ZsZewWyUJ5FsKp9lMwv4tYbNlegQilM7');

        $this->setupEloquent($app);
    }

    /**
     * @param  Application  $app
     */
    protected function setupCache(Application $app)
    {
        /** @var Repository $appConfig */
        $appConfig = $app['config'];

        $appConfig->set('cache', [
            'default' => 'file',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                    'serialize' => true,
                ],
                'file' => [
                    'driver' => 'file',
                    'path' => '/tmp/test/',
                ],
            ],
        ]);
    }

    /**
     * @param  Application  $app
     * @throws Exception
     */
    protected function setupEloquent(Application $app)
    {
        /** @var Repository $appConfig */
        $appConfig = $app['config'];

        $file = sprintf('/tmp/%s-%s-database.sqlite', date('Y-m-d'), md5(random_bytes(100)));
        touch($file);

        $appConfig->set('database', [
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'url' => '',
                    'database' => $file,
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
            ],
        ]);
    }

    /**
     * @param  string|object  $object
     * @param  string  $method
     * @param  array  $args
     * @return mixed
     * @throws ReflectionException
     */
    protected static function callMethod($object, string $method, array $args = [])
    {
        $class = new ReflectionClass($object);

        /** @var ReflectionMethod $method */
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs((is_object($object) ? $object : null), $args);
    }


    /**
     * @param  string|object  $object
     * @param  string  $name
     * @return mixed
     * @throws ReflectionException
     */
    protected static function getProperty($object, string $name)
    {
        $class = new ReflectionClass($object);

        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    /**
     * @param  string|object  $object
     * @param  string  $name
     * @param $value
     * @throws ReflectionException
     */
    protected static function setProperty($object, string $name, $value)
    {
        $class = new ReflectionClass($object);

        $property = $class->getProperty($name);
        $property->setAccessible(true);

        $property->setValue($object, $value);
    }
}
