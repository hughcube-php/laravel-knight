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
use HughCube\Laravel\Knight\Http\Actions\PhpInfoAction;
use HughCube\Laravel\Knight\Http\Actions\PingAction;
use HughCube\Laravel\Knight\Http\Actions\RequestLogAction;
use HughCube\Laravel\Knight\Http\Actions\RequestShowAction;
use HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin;
use HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin;
use HughCube\Laravel\Knight\Mixin\Http\RequestMixin;
use HughCube\Laravel\Knight\Mixin\Support\CollectionMixin;
use HughCube\Laravel\Knight\Mixin\Support\StrMixin;
use HughCube\Laravel\Knight\OPcache\Actions\ScriptsAction as OPcacheScriptsAction;
use HughCube\Laravel\Knight\OPcache\Actions\StatesAction as OPcacheStatesAction;
use HughCube\Laravel\Knight\OPcache\Commands\ClearCliCacheCommand as OPcacheClearCliCacheCommand;
use HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand as OPcacheCompileFilesCommand;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
    protected $routesAreCached = null;

    /**
     * Register the provider.
     *
     * @throws ReflectionException
     */
    public function register()
    {
        Str::mixin(new StrMixin(), false);
        Collection::mixin(new CollectionMixin(), false);
        Request::mixin(new RequestMixin(), false);

        /** 数据库 */
        Grammar::mixin(new GrammarMixin(), false);
        Builder::mixin(new BuilderMixin(), false);
    }

    /**
     * Boot the provider.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([Config::class, Environment::class, PhpIniFile::class, KRTest::class]);
        }

        $this->bootOPcache();
        $this->bootRequest();
        $this->bootPhpInfo();
        $this->bootHealthCheck();

        $this->registerRefreshModelCacheEvent();
    }

    protected function hasRoutesCache(): bool
    {
        if (null === $this->routesAreCached) {
            $this->routesAreCached = $this->app->routesAreCached();
        }

        return $this->routesAreCached;
    }

    protected function bootOPcache()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                OPcacheCompileFilesCommand::class,
                OPcacheClearCliCacheCommand::class,
            ]);
        }

        if (!$this->hasRoutesCache() && false !== ($prefix = config('knight.opcache.route_prefix', false))) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/opcache/scripts', OPcacheScriptsAction::class)->name('knight.opcache.scripts');
                Route::any('/opcache/states', OPcacheStatesAction::class)->name('knight.opcache.states');
            });
        }
    }

    protected function bootRequest()
    {
        if (!$this->hasRoutesCache() && false !== ($prefix = config('knight.request.route_prefix', false))) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/request/log', RequestLogAction::class)->name('knight.request.log');
                Route::any('/request/show', RequestShowAction::class)->name('knight.request.show');
            });
        }
    }

    protected function bootHealthCheck()
    {
        if (!$this->hasRoutesCache() && false !== ($prefix = config('knight.healthcheck.route_prefix'))) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/healthcheck', PingAction::class)->name('knight.healthcheck');
            });
        }
    }

    protected function bootPhpInfo()
    {
        if (!$this->hasRoutesCache() && false !== ($prefix = config('knight.phpinfo.route_prefix', false))) {
            Route::group(['prefix' => $prefix], function () {
                Route::any('/phpinfo', PhpInfoAction::class)->name('knight.phpinfo');
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
            'eloquent.created: *',
            'eloquent.deleted: *',
            'eloquent.updated: *',
            'eloquent.restored: *',
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
