<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/10
 * Time: 4:29 下午.
 */

namespace HughCube\Laravel\Knight;

use HughCube\Laravel\Knight\Console\Commands\Config;
use HughCube\Laravel\Knight\Console\Commands\Environment;
use HughCube\Laravel\Knight\Console\Commands\KRTest;
use HughCube\Laravel\Knight\Console\Commands\PhpIniFile;
use HughCube\Laravel\Knight\Http\Actions\PingAction;
use HughCube\Laravel\Knight\Http\Actions\RequestLogAction;
use HughCube\Laravel\Knight\Http\Actions\RequestShowAction;
use HughCube\Laravel\Knight\OPcache\Actions\ScriptsAction as OPcacheScriptsAction;
use HughCube\Laravel\Knight\OPcache\Actions\StatesAction as OPcacheStatesAction;
use HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand as OPcacheCompileFilesCommand;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

/**
 * @property LaravelApplication|LumenApplication $app
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Boot the provider.
     */
    public function boot()
    {
        $source = realpath(dirname(__DIR__).'/config/knight.php');
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('knight.php')]);
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('knight');
        }

        $this->commands([Config::class]);
        $this->commands([Environment::class]);
        $this->commands([PhpIniFile::class]);
        $this->commands([KRTest::class]);

        $this->bootOPcache();
        $this->bootRequest();
        $this->bootPing();
    }

    /**
     * Register the provider.
     */
    public function register()
    {
    }

    protected function bootOPcache()
    {
        $this->commands([OPcacheCompileFilesCommand::class]);

        $prefix = config('knight.opcache.route_prefix');
        if (!$this->app->routesAreCached() && !empty($prefix)) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/scripts', OPcacheScriptsAction::class)->name('knight_opcache_scripts');
                Route::any('/states', OPcacheStatesAction::class)->name('knight_opcache_states');
            });
        }
    }

    /**
     * Define the Sanctum routes.
     *
     * @return void
     */
    protected function bootRequest()
    {
        $prefix = config('knight.request.route_prefix');
        if (!$this->app->routesAreCached() && !empty($prefix)) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/log', RequestLogAction::class)->name('knight_request_log');
                Route::any('/show', RequestShowAction::class)->name('knight_request_show');
            });
        }
    }

    /**
     * Define the Sanctum routes.
     *
     * @return void
     */
    protected function bootPing()
    {
        $enable = false !== config('knight.ping.routes');
        if (!$this->app->routesAreCached() && $enable) {
            Route::group(['prefix' => config('knight.ping.route_prefix')], function () {
                Route::any('/ping', PingAction::class)->name('knight_ping');
            });
        }
    }
}
