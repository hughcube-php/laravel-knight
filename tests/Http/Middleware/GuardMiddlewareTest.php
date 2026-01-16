<?php

namespace HughCube\Laravel\Knight\Tests\Http\Middleware;

use HughCube\Laravel\Knight\Http\Middleware\OnlyDebugModeGuard;
use HughCube\Laravel\Knight\Http\Middleware\OnlyIpGuard;
use HughCube\Laravel\Knight\Http\Middleware\OnlyLocalEnvGuard;
use HughCube\Laravel\Knight\Http\Middleware\OnlyLocalGuard;
use HughCube\Laravel\Knight\Http\Middleware\OnlyPrivateIpGuard;
use HughCube\Laravel\Knight\Http\Middleware\OnlyProdEnvGuard;
use HughCube\Laravel\Knight\Http\Middleware\OnlyPublicIpGuard;
use HughCube\Laravel\Knight\Http\Middleware\OnlyTestEnvGuard;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class GuardMiddlewareTest extends TestCase
{
    private function makeRequestWithIp(string $ip): Request
    {
        return Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $ip]);
    }

    private function setEnvironment(string $env): void
    {
        $this->app->detectEnvironment(function () use ($env) {
            return $env;
        });
    }

    public function testOnlyDebugModeGuardAllowsWhenDebug()
    {
        config(['app.debug' => true]);

        $middleware = new OnlyDebugModeGuard();
        $response = $middleware->handle(Request::create('/', 'GET'), function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }

    public function testOnlyDebugModeGuardDeniesWhenNotDebug()
    {
        config(['app.debug' => false]);

        $middleware = new OnlyDebugModeGuard();

        $this->expectException(AccessDeniedHttpException::class);
        $middleware->handle(Request::create('/', 'GET'), function () {
            return new Response('ok');
        });
    }

    public function testOnlyIpGuardAllowsMatchingIp()
    {
        $middleware = new OnlyIpGuard();
        $response = $middleware->handle($this->makeRequestWithIp('10.0.0.1'), function () {
            return new Response('ok');
        }, '10.0.0.1');

        $this->assertSame('ok', $response->getContent());
    }

    public function testOnlyIpGuardDeniesOtherIp()
    {
        $middleware = new OnlyIpGuard();

        $this->expectException(AccessDeniedHttpException::class);
        $middleware->handle($this->makeRequestWithIp('10.0.0.1'), function () {
            return new Response('ok');
        }, '127.0.0.1');
    }

    public function testOnlyLocalEnvGuardAllowsLocalEnv()
    {
        $this->setEnvironment('local');

        $middleware = new OnlyLocalEnvGuard();
        $response = $middleware->handle(Request::create('/', 'GET'), function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }

    public function testOnlyLocalEnvGuardDeniesNonLocalEnv()
    {
        $this->setEnvironment('production');

        $middleware = new OnlyLocalEnvGuard();

        $this->expectException(AccessDeniedHttpException::class);
        $middleware->handle(Request::create('/', 'GET'), function () {
            return new Response('ok');
        });
    }

    public function testOnlyLocalGuardAllowsAllWhenLocalEnv()
    {
        $this->setEnvironment('local');

        $middleware = new OnlyLocalGuard();
        $response = $middleware->handle($this->makeRequestWithIp('8.8.8.8'), function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }

    public function testOnlyLocalGuardDeniesNonLocalEnv()
    {
        $this->setEnvironment('production');

        $middleware = new OnlyLocalGuard();

        $this->expectException(AccessDeniedHttpException::class);
        $middleware->handle($this->makeRequestWithIp('8.8.8.8'), function () {
            return new Response('ok');
        });
    }

    public function testOnlyPrivateIpGuardAllowsPrivateIp()
    {
        $middleware = new OnlyPrivateIpGuard();
        $response = $middleware->handle($this->makeRequestWithIp('192.168.0.1'), function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }

    public function testOnlyPrivateIpGuardDeniesPublicIp()
    {
        $middleware = new OnlyPrivateIpGuard();

        $this->expectException(AccessDeniedHttpException::class);
        $middleware->handle($this->makeRequestWithIp('8.8.8.8'), function () {
            return new Response('ok');
        });
    }

    public function testOnlyProdEnvGuardAllowsProdEnv()
    {
        $this->setEnvironment('prod');

        $middleware = new OnlyProdEnvGuard();
        $response = $middleware->handle(Request::create('/', 'GET'), function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }

    public function testOnlyProdEnvGuardDeniesNonProdEnv()
    {
        $this->setEnvironment('local');

        $middleware = new OnlyProdEnvGuard();

        $this->expectException(AccessDeniedHttpException::class);
        $middleware->handle(Request::create('/', 'GET'), function () {
            return new Response('ok');
        });
    }

    public function testOnlyPublicIpGuardAllowsPublicIp()
    {
        $middleware = new OnlyPublicIpGuard();
        $response = $middleware->handle($this->makeRequestWithIp('8.8.8.8'), function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }

    public function testOnlyPublicIpGuardDeniesPrivateIp()
    {
        $middleware = new OnlyPublicIpGuard();

        $this->expectException(AccessDeniedHttpException::class);
        $middleware->handle($this->makeRequestWithIp('192.168.0.1'), function () {
            return new Response('ok');
        });
    }

    public function testOnlyTestEnvGuardAllowsTestEnv()
    {
        $this->setEnvironment('test');

        $middleware = new OnlyTestEnvGuard();
        $response = $middleware->handle(Request::create('/', 'GET'), function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
    }

    public function testOnlyTestEnvGuardDeniesNonTestEnv()
    {
        $this->setEnvironment('testing');

        $middleware = new OnlyTestEnvGuard();

        $this->expectException(AccessDeniedHttpException::class);
        $middleware->handle(Request::create('/', 'GET'), function () {
            return new Response('ok');
        });
    }
}
