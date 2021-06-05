<?php

namespace HughCube\Laravel\Knight\Console;

use HughCube\Laravel\Knight\Console\Commands\Environment;
use HughCube\Laravel\Knight\Console\Commands\PhpIniFile;
use HughCube\Laravel\Knight\Console\Commands\RepeatTest;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

/**
 * Class CommandServiceProvider.
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->registerCommand();
    }

    protected function registerCommand()
    {
        $this->commands([
            Environment::class,
            PhpIniFile::class,
            RepeatTest::class,
        ]);
    }
}
