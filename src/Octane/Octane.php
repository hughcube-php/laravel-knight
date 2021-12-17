<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:56
 */

namespace HughCube\Laravel\Knight\Octane;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Octane\Contracts\DispatchesTasks;
use Psr\SimpleCache\InvalidArgumentException;

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
     * @param  callable  $callable
     * @return void
     */
    public static function task(callable $callable)
    {
        static::tasks()->dispatch([$callable]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function waitTasks($count = 10)
    {
        /** 生成标识 */
        $spies = [];
        for ($i = 1; $i <= $count; $i++) {
            $spies[] = sprintf('%s-%s', Str::random(32), $i);
        }

        /** 异步写入探针 */
        foreach ($spies as $spy) {
            static::task(function () use ($spy) {
                static::cache()->set($spy, time(), 300);
            });
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
    }
}
