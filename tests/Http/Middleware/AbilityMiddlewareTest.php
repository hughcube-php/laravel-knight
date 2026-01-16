<?php

namespace HughCube\Laravel\Knight\Tests\Http\Middleware;

use HughCube\Laravel\Knight\Http\Middleware\CheckAbilities;
use HughCube\Laravel\Knight\Http\Middleware\CheckForAnyAbility;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AbilityMiddlewareTest extends TestCase
{
    public function testCheckAbilitiesSkipsWhenNoUser()
    {
        $request = Request::create('/abilities', 'GET');
        $request->setUserResolver(function () {
            return null;
        });

        $middleware = new CheckAbilities();
        $response = $middleware->handle($request, function () {
            return new Response('ok');
        }, 'ability');

        $this->assertSame('ok', $response->getContent());
    }

    public function testCheckAbilitiesSkipsWhenNoToken()
    {
        $request = Request::create('/abilities', 'GET');
        $request->setUserResolver(function () {
            return new class() {
                public function currentAccessToken()
                {
                    return null;
                }
            };
        });

        $middleware = new CheckAbilities();
        $response = $middleware->handle($request, function () {
            return new Response('ok');
        }, 'ability');

        $this->assertSame('ok', $response->getContent());
    }

    public function testCheckForAnyAbilitySkipsWhenNoUser()
    {
        $request = Request::create('/abilities', 'GET');
        $request->setUserResolver(function () {
            return null;
        });

        $middleware = new CheckForAnyAbility();
        $response = $middleware->handle($request, function () {
            return new Response('ok');
        }, 'ability');

        $this->assertSame('ok', $response->getContent());
    }

    public function testCheckForAnyAbilitySkipsWhenNoToken()
    {
        $request = Request::create('/abilities', 'GET');
        $request->setUserResolver(function () {
            return new class() {
                public function currentAccessToken()
                {
                    return null;
                }
            };
        });

        $middleware = new CheckForAnyAbility();
        $response = $middleware->handle($request, function () {
            return new Response('ok');
        }, 'ability');

        $this->assertSame('ok', $response->getContent());
    }
}
