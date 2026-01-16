<?php

namespace HughCube\Laravel\Knight\Tests\OPcache\Actions;

use Exception;
use HughCube\Laravel\Knight\OPcache\Actions\ResetAction;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ResetActionTest extends TestCase
{
    public function testActionHandlesReset()
    {
        Log::spy();

        $action = new ResetAction();
        $request = Request::create('/opcache/reset', 'POST');
        $this->app->instance('request', $request);

        if (!extension_loaded('Zend OPcache')) {
            $this->expectException(Exception::class);
            $this->callMethod($action, 'action');
            return;
        }

        try {
            $response = $this->callMethod($action, 'action');
            $this->assertInstanceOf(Response::class, $response);
        } catch (Exception $exception) {
            $this->assertSame('Failed to reset OPcache.', $exception->getMessage());
        }
    }
}
