<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18.
 */

namespace HughCube\Laravel\Knight\OPcache\Commands;

require_once __DIR__.'/../Support/OpcacheOverrides.php';

use HughCube\Laravel\Knight\Tests\OPcache\OpcacheTestOverrides;

if (!function_exists(__NAMESPACE__.'\extension_loaded')) {
    function extension_loaded($name): bool
    {
        return OpcacheTestOverrides::$extensionLoaded;
    }
}

if (!function_exists(__NAMESPACE__.'\opcache_get_status')) {
    function opcache_get_status()
    {
        return OpcacheTestOverrides::$opcacheStatusEnabled ? ['enabled' => true] : false;
    }
}

if (!function_exists(__NAMESPACE__.'\opcache_reset')) {
    function opcache_reset(): bool
    {
        return OpcacheTestOverrides::$opcacheResetResult;
    }
}

namespace HughCube\Laravel\Knight\Tests\OPcache\Commands;

use Exception;
use HughCube\Laravel\Knight\OPcache\Commands\ClearCliCacheCommand;
use HughCube\Laravel\Knight\Tests\OPcache\OpcacheTestOverrides;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ClearCliCacheCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OpcacheTestOverrides::resetDefaults();
    }

    public function testHandleLogsWhenExtensionMissing()
    {
        Log::spy();

        OpcacheTestOverrides::$extensionLoaded = false;
        OpcacheTestOverrides::$opcacheStatusEnabled = false;

        $this->makeCommand()->handle(new Schedule($this->app));

        Log::shouldHaveReceived('warning')->withArgs(function ($message) {
            return is_string($message) && str_contains($message, 'extension not loaded');
        });
    }

    public function testHandleLogsWhenOpcacheDisabled()
    {
        Log::spy();

        OpcacheTestOverrides::$extensionLoaded = true;
        OpcacheTestOverrides::$opcacheStatusEnabled = false;

        $this->makeCommand()->handle(new Schedule($this->app));

        Log::shouldHaveReceived('warning')->withArgs(function ($message) {
            return is_string($message) && str_contains($message, 'not enabled');
        });
    }

    public function testHandleThrowsWhenResetFails()
    {
        OpcacheTestOverrides::$extensionLoaded = true;
        OpcacheTestOverrides::$opcacheStatusEnabled = true;
        OpcacheTestOverrides::$opcacheResetResult = false;

        $command = $this->makeCommand();

        $this->expectException(Exception::class);
        $command->handle(new Schedule($this->app));
    }

    public function testHandleLogsWhenResetSucceeds()
    {
        Log::spy();

        OpcacheTestOverrides::$extensionLoaded = true;
        OpcacheTestOverrides::$opcacheStatusEnabled = true;
        OpcacheTestOverrides::$opcacheResetResult = true;

        $command = $this->makeCommand();
        $command->handle(new Schedule($this->app));

        Log::shouldHaveReceived('info')->withArgs(function ($message) {
            return is_string($message) && str_contains($message, 'OPcache CLI cleared');
        });
    }

    private function makeCommand(): ClearCliCacheCommand
    {
        $command = new ClearCliCacheCommand();
        $command->setLaravel($this->app);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput()));

        return $command;
    }
}
