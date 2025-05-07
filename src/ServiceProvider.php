<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/10
 * Time: 4:29 下午.
 */

namespace HughCube\Laravel\Knight;

use Carbon\Carbon;
use HughCube\Laravel\Knight\Auth\ModelUserProvider;
use HughCube\Laravel\Knight\Console\Commands\Config;
use HughCube\Laravel\Knight\Console\Commands\Environment;
use HughCube\Laravel\Knight\Console\Commands\KRTest;
use HughCube\Laravel\Knight\Console\Commands\PhpIniFile;
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Http\Actions\PhpInfoAction;
use HughCube\Laravel\Knight\Http\Actions\PingAction;
use HughCube\Laravel\Knight\Http\Actions\RequestLogAction;
use HughCube\Laravel\Knight\Http\Actions\RequestShowAction;
use HughCube\Laravel\Knight\Mixin\Database\Eloquent\CollectionMixin as EloquentCollectionMixin;
use HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin;
use HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin;
use HughCube\Laravel\Knight\Mixin\Support\CarbonMixin;
use HughCube\Laravel\Knight\Mixin\Support\CollectionMixin;
use HughCube\Laravel\Knight\OPcache\Actions\ScriptsAction as OPcacheScriptsAction;
use HughCube\Laravel\Knight\OPcache\Actions\StatesAction as OPcacheStatesAction;
use HughCube\Laravel\Knight\OPcache\Commands\ClearCliCacheCommand as OPcacheClearCliCacheCommand;
use HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand as OPcacheCompileFilesCommand;
use HughCube\Laravel\Knight\OPcache\Commands\CreatePreloadCommand as OPcacheCreatePreloadCommand;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use ReflectionException;

/**
 * @property LaravelApplication|LumenApplication $app
 */
class ServiceProvider extends IlluminateServiceProvider
{
    protected ?bool $routesAreCached = null;

    /**
     * Register the provider.
     *
     * @throws ReflectionException
     */
    public function register()
    {
        Collection::mixin(new CollectionMixin(), false);

        /** 数据库 */
        Grammar::mixin(new GrammarMixin(), false);
        Builder::mixin(new BuilderMixin(), false);
        EloquentCollection::mixin(new EloquentCollectionMixin(), false);

        /** Carbon */
        Carbon::mixin(new CarbonMixin());
    }

    /**
     * Boot the provider.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Config::class,
                Environment::class,
                PhpIniFile::class,
                KRTest::class,
            ]);
        }

        $this->bootOpCache();
        $this->bootRequest();
        $this->bootPhpInfo();
        $this->bootHealthCheck();
        $this->configureAuthUserProvider();

        $this->registerRefreshModelCacheEvent();
    }

    protected function hasRoutesCache(): bool
    {
        return $this->routesAreCached ??= $this->app->routesAreCached();
    }

    protected function bootOpCache()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                OPcacheCompileFilesCommand::class,
                OPcacheClearCliCacheCommand::class,
                OPcacheCreatePreloadCommand::class,
            ]);
        }

        /** @var Repository $config */
        $config = $this->app->make('config');
        if (!$this->hasRoutesCache() && false !== ($prefix = $config->get('knight.opcache.route_prefix', false))) {
            Route::group(['prefix' => $prefix], function () use ($config) {
                Route::any(
                    '/opcache/scripts',
                    $config->get('knight.opcache.action.scripts', OPcacheScriptsAction::class)
                )->name('knight.opcache.scripts');

                Route::any(
                    '/opcache/states',
                    $config->get('knight.opcache.action.states', OPcacheStatesAction::class)
                )->name('knight.opcache.states');
            });
        }
    }

    protected function bootRequest()
    {
        /** @var Repository $config */
        $config = $this->app->make('config');
        if (!$this->hasRoutesCache() && false !== ($prefix = $config->get('knight.request.route_prefix', false))) {
            Route::group(['prefix' => $prefix], function () use ($config) {
                Route::any(
                    '/request/log',
                    $config->get('knight.request.action.log', RequestLogAction::class)
                )->name('knight.request.log');

                Route::any(
                    '/request/show',
                    $config->get('knight.request.action.show', RequestShowAction::class)
                )->name('knight.request.show');
            });
        }
    }

    protected function bootHealthCheck()
    {
        /** @var Repository $config */
        $config = $this->app->make('config');
        if (!$this->hasRoutesCache() && false !== ($prefix = $config->get('knight.healthcheck.route_prefix'))) {
            Route::group(['prefix' => $prefix], function () use ($config) {
                Route::any(
                    '/healthcheck',
                    $config->get('knight.healthcheck.action.healthcheck', PingAction::class)
                )->name('knight.healthcheck');
            });
        }
    }

    protected function bootPhpInfo()
    {
        /** @var Repository $config */
        $config = $this->app->make('config');
        if (!$this->hasRoutesCache() && false !== ($prefix = $config->get('knight.phpinfo.route_prefix', false))) {
            Route::group(['prefix' => $prefix], function () use ($config) {
                Route::any(
                    '/phpinfo',
                    $config->get('knight.phpinfo.action.phpinfo', PhpInfoAction::class)
                )->name('knight.phpinfo');
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
                /** @phpstan-ignore-next-line */
                if (method_exists($model, 'deleteRowCache')) {
                    $model->deleteRowCache();
                }
            }
        });
    }

    protected function configureAuthUserProvider()
    {
        Auth::resolved(function ($auth) {
            $auth->provider('knight-model', function ($app, $name, array $config) use ($auth) {
                return new ModelUserProvider($this->app['hash'], $config['model']);
            });
        });
    }
}
