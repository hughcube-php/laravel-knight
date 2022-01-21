<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:56.
 */

namespace HughCube\Laravel\Knight\Octane;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Octane\Contracts\DispatchesTasks;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use Psr\SimpleCache\InvalidArgumentException;
use Swoole\Http\Server;

/**
 * @method static DispatchesTasks tasks()
 *
 * @mixin  \Laravel\Octane\Octane
 */
class Octane extends \Laravel\Octane\Facades\Octane
{
    /**
     * @return Repository
     */
    public static function cache(): Repository
    {
        return Cache::store('octane');
    }

    /**
     * @param callable $callable
     *
     * @return void
     */
    public static function task(callable $callable)
    {
        static::tasks()->dispatch([$callable]);
    }

    /**
     * @throws InvalidArgumentException
     * @throws PhpVersionNotSupportedException
     *
     * @return int
     */
    public static function waitSwooleTasks(): int
    {
        $workerCount = 0;
        if (class_exists(Server::class) && app()->bound(Server::class)) {
            $workerCount = app(Server::class)->setting['task_worker_num'] ?? 0;
        }

        /** 生成标识 */
        $spies = [];
        for ($i = 1; $i <= $workerCount; $i++) {
            $random = serialize([microtime(), Str::random(32), $i]);
            $spies[] = sprintf('%s-%s-%s', getmypid(), md5($random), crc32($random));
        }

        /** 异步写入探针 */
        foreach ($spies as $index => $spy) {
            app(Server::class)->task(new SerializableClosure(function () use ($spy) {
                Cache::store('octane')->put($spy, time(), 600);
            }), $index);
        }

        /** 等待异步任务完成 */
        while (!empty($spies)) {
            foreach ($spies as $index => $spy) {
                $timestamp = static::cache()->get($spy);
                if (is_numeric($timestamp) && 0 < $timestamp) {
                    unset($spies[$index]);
                    static::cache()->forget($spy);
                }
            }
            usleep(200);
        }

        return $workerCount;
    }
}
