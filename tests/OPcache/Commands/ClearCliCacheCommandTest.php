<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18.
 */

namespace HughCube\Laravel\Knight\Tests\OPcache\Commands;

use Exception;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\Log;

class ClearCliCacheCommandTest extends TestCase
{
    public function testHandleLogsWhenOpcacheUnavailable()
    {
        Log::spy();

        if (!extension_loaded('Zend OPcache') || !function_exists('opcache_get_status')) {
            $this->artisan('opcache:clear-cli-cache')->assertExitCode(0);

            Log::shouldHaveReceived('warning')->withArgs(function ($message) {
                return is_string($message) && str_contains($message, 'extension not loaded');
            });

            return;
        }

        if (false === @opcache_get_status()) {
            $this->artisan('opcache:clear-cli-cache')->assertExitCode(0);

            Log::shouldHaveReceived('warning')->withArgs(function ($message) {
                return is_string($message) && str_contains($message, 'not enabled');
            });

            return;
        }

        try {
            $this->artisan('opcache:clear-cli-cache')->assertExitCode(0);
        } catch (Exception $exception) {
            $this->assertSame('Failed to reset OPcache.', $exception->getMessage());
        }
    }
}
