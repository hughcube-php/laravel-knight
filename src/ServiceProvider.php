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
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Http\Actions\PingAction;
use HughCube\Laravel\Knight\Http\Actions\RequestLogAction;
use HughCube\Laravel\Knight\Http\Actions\RequestShowAction;
use HughCube\Laravel\Knight\Mixin\Support\StrMixin;
use HughCube\Laravel\Knight\OPcache\Actions\ScriptsAction as OPcacheScriptsAction;
use HughCube\Laravel\Knight\OPcache\Actions\StatesAction as OPcacheStatesAction;
use HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand as OPcacheCompileFilesCommand;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Support\Str;
use Laravel\Lumen\Application as LumenApplication;
use ReflectionException;

/**
 * @property LaravelApplication|LumenApplication $app
 */
class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register the provider.
     *
     * @throws ReflectionException
     */
    public function register()
    {
        Str::mixin(new StrMixin());
    }

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

        $this->registerRefreshModelCacheEvent();
    }

    protected function bootOPcache()
    {
        $this->commands([OPcacheCompileFilesCommand::class]);

        $prefix = config('knight.opcache.route_prefix', false);
        if (!$this->app->routesAreCached() && false !== $prefix) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/opcache/scripts', OPcacheScriptsAction::class)->name('knight.opcache.scripts');
                Route::any('/opcache/states', OPcacheStatesAction::class)->name('knight.opcache.states');
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
        $prefix = config('knight.request.route_prefix', false);
        if (!$this->app->routesAreCached() && false !== $prefix) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/request/log', RequestLogAction::class)->name('knight.request.log');
                Route::any('/request/show', RequestShowAction::class)->name('knight.request.show');
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
        $prefix = config('knight.request.route_prefix', '');
        if (!$this->app->routesAreCached() && false !== $prefix) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/ping', PingAction::class)->name('knight.ping');
            });
        }
    }

    /**
     * @return void
     *
     * @see \Illuminate\Database\Eloquent\Concerns\HasEvents::getObservableEvents
     */
    protected function registerRefreshModelCacheEvent()
    {
        $dispatcher = EloquentModel::getEventDispatcher();
        if (!$dispatcher instanceof Dispatcher) {
            return;
        }

        $events = [
            'eloquent.created: *', 'eloquent.deleted: *',
            'eloquent.updated: *', 'eloquent.restored: *',
        ];
        $dispatcher->listen($events, function ($event, $models) {
            /** @var Model $model */
            foreach ($models as $model) {
                if (method_exists($model, 'refreshRowCache')) {
                    $model->refreshRowCache();
                }
            }
        });
    }
}
