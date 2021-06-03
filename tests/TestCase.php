<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 11:36 下午.
 */

namespace HughCube\Laravel\Package\Tests;

use HughCube\Laravel\Package\ServiceProvider as PackageServiceProvider;
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
            PackageServiceProvider::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->setupCache($app);

        /** @var \Illuminate\Config\Repository $appConfig */
        $appConfig = $app['config'];
        $appConfig->set('captchaCode', (require dirname(__DIR__) . '/config/config.php'));
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function setupCache($app)
    {
        /** @var \Illuminate\Config\Repository $appConfig */
        $appConfig = $app['config'];

        $appConfig->set('cache', [
            'default' => 'default',
            'stores' => [
                'default' => [
                    'driver' => 'file',
                    'path' => sprintf('/tmp/test/%s', md5(serialize([__METHOD__]))),
                ],
            ],
        ]);
    }
}
