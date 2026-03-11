<?php

namespace HughCube\Laravel\Knight\Support;

use Illuminate\Support\Str;

class Runtime
{
    /**
     * 是否在 CLI 模式下运行.
     */
    public static function isConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * 当前运行的 artisan 命令名（非 CLI 返回 null）.
     */
    public static function getCommand(): ?string
    {
        if (!static::isConsole()) {
            return null;
        }

        $command = $_SERVER['argv'][1] ?? null;

        return is_string($command) ? $command : null;
    }

    /**
     * 是否正在运行指定命令（支持通配符，如 'ide-helper:*'）.
     */
    public static function isRunningCommand(string $pattern): bool
    {
        $command = static::getCommand();

        if (null === $command) {
            return false;
        }

        return Str::is($pattern, $command, true);
    }

    /**
     * 是否在 Octane worker 中运行.
     */
    public static function isOctane(): bool
    {
        return app()->bound('octane');
    }

    /**
     * 是否在队列 worker 中运行.
     */
    public static function isQueueWorker(): bool
    {
        return static::isRunningCommand('queue:work')
            || static::isRunningCommand('queue:listen')
            || static::isRunningCommand('horizon');
    }

    /**
     * 当前应用环境是否匹配.
     */
    public static function isEnv(string $env): bool
    {
        return $env === app('config')->get('app.env', 'production');
    }

    /**
     * 是否为本地开发环境.
     */
    public static function isLocal(): bool
    {
        return static::isEnv('local');
    }

    /**
     * 是否为生产环境.
     */
    public static function isProduction(): bool
    {
        return static::isEnv('production');
    }

    /**
     * 是否开启了调试模式.
     */
    public static function isDebug(): bool
    {
        return true == app('config')->get('app.debug');
    }
}
