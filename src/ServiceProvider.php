<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/10
 * Time: 4:29 下午
 */

namespace HughCube\Laravel\Knight;

use HughCube\Laravel\Knight\Console\Commands\Config;
use HughCube\Laravel\Knight\Console\Commands\Environment;
use HughCube\Laravel\Knight\Console\Commands\PhpIniFile;
use HughCube\Laravel\Knight\Console\Commands\RTest;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Boot the provider.
     */
    public function boot()
    {
    }

    /**
     * Register the provider.
     */
    public function register()
    {
        $this->registerCommand();
    }

    protected function registerCommand()
    {
        $this->commands([
            Config::class,
            Environment::class,
            PhpIniFile::class,
            RTest::class
        ]);
    }
}
