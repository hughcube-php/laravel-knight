<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 11:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests;

use Illuminate\Database\DatabaseServiceProvider;
use Illuminate\Http\Request;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            DatabaseServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'ZsZewWyUJ5FsKp9lMwv4tYbNlegQilM7');

        $this->setupEloquent($app);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setupCache($app)
    {
        /** @var \Illuminate\Config\Repository $appConfig */
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
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setupEloquent($app)
    {
        /** @var \Illuminate\Config\Repository $appConfig */
        $appConfig = $app['config'];

        $file = sprintf('/tmp/%s-%s-database.sqlite', date('Y-m-d'), md5(random_bytes(100)));
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
}
