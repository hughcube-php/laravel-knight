<?php

namespace HughCube\Laravel\Knight\OPcache;

require_once __DIR__.'/../Support/OpcacheOverrides.php';

use HughCube\Laravel\Knight\Tests\OPcache\OpcacheTestOverrides;

if (!function_exists(__NAMESPACE__.'\extension_loaded')) {
    function extension_loaded($name): bool
    {
        return OpcacheTestOverrides::$extensionLoaded;
    }
}

namespace HughCube\Laravel\Knight\OPcache\Actions;

use HughCube\Laravel\Knight\Tests\OPcache\OpcacheTestOverrides;

if (!function_exists(__NAMESPACE__.'\opcache_reset')) {
    function opcache_reset(): bool
    {
        return OpcacheTestOverrides::$opcacheResetResult;
    }
}

namespace HughCube\Laravel\Knight\Tests\OPcache\Actions;

use Exception;
use HughCube\Laravel\Knight\OPcache\Actions\ResetAction;
use HughCube\Laravel\Knight\Tests\OPcache\OpcacheTestOverrides;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ResetActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        OpcacheTestOverrides::resetDefaults();
    }

    public function testActionThrowsWhenExtensionMissing()
    {
        $action = $this->makeAction();

        OpcacheTestOverrides::$extensionLoaded = false;
        OpcacheTestOverrides::$opcacheResetResult = true;

        $this->expectException(Exception::class);
        $this->callMethod($action, 'action');
    }

    public function testActionThrowsWhenResetFails()
    {
        $action = $this->makeAction();

        OpcacheTestOverrides::$extensionLoaded = true;
        OpcacheTestOverrides::$opcacheResetResult = false;

        try {
            $this->callMethod($action, 'action');
            $this->fail('Expected Exception was not thrown.');
        } catch (Exception $exception) {
            $this->assertSame('Failed to reset OPcache.', $exception->getMessage());
        }
    }

    public function testActionSucceedsWhenResetOk()
    {
        Log::spy();

        $action = $this->makeAction();

        OpcacheTestOverrides::$extensionLoaded = true;
        OpcacheTestOverrides::$opcacheResetResult = true;

        $response = $this->callMethod($action, 'action');

        $this->assertInstanceOf(Response::class, $response);
        Log::shouldHaveReceived('info')->withArgs(function ($message) {
            return is_string($message) && str_contains($message, 'OPcache reset');
        });
    }

    private function makeAction(): ResetAction
    {
        $action = new ResetAction();
        $request = Request::create('/opcache/reset', 'POST');
        $this->app->instance('request', $request);

        return $action;
    }
}
