<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 7:36 下午
 */

namespace HughCube\Laravel\Knight\Tests\Routing;

use Closure;
use HughCube\Laravel\Knight\Http\ParameterBag;
use HughCube\Laravel\Knight\Routing\Action;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use ReflectionClass;

class ActionTest extends TestCase
{
    /**
     * Define web routes setup.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    protected function defineWebRoutes($router)
    {
        $action = $this
            ->getMockBuilder(Action::class)
            ->onlyMethods(['action'])
            ->getMockForAbstractClass();

        $action->expects($this->any())->method('action')->willReturn("test");

        $router->POST("/test", get_class($action));
    }

    public function testGetRequest()
    {
        $action = $this->getMockForAbstractClass(Action::class);

        $getRequest = Closure::bind(function () {
            /** @var Action $this */
            return $this->getRequest();
        }, $action, Action::class);

        $this->assertInstanceOf(Request::class, $getRequest());
    }

    /**
     *
     * @covers \HughCube\Laravel\Knight\Routing\Action::rules
     * @throws \Exception
     */
    public function testGetParameter()
    {
        $action = $this
            ->getMockBuilder(Action::class)
            ->onlyMethods(['getRequest', 'rules'])
            ->getMockForAbstractClass();

        $key = $this->requestClearKey;

        $action->expects($this->any())->method('getRequest')->willReturn($this->createRequest());
        $action->expects($this->any())->method('rules')->willReturn([$key => ['integer']]);

        $getParameter = Closure::bind(function () {
            /** @var Action $this */
            return $this->getParameter();
        }, $action, Action::class);

        $getRequest = Closure::bind(function () {
            /** @var Action $this */
            return $this->getRequest();
        }, $action, Action::class);

        $this->assertInstanceOf(ParameterBag::class, $getParameter());
        $this->assertTrue($getParameter()->has($key));
        $this->assertSame($getParameter()->get($key), $getRequest()->json($key));
        $this->assertSame(1, count($getParameter()->all()));
    }

    public function testInvoke()
    {
        $action = $this
            ->getMockBuilder(Action::class)
            ->onlyMethods(['action', 'getRequest', 'rules'])
            ->getMockForAbstractClass();

        $getParameter = Closure::bind(function () {
            /** @var Action $this */
            return $this->getParameter();
        }, $action, Action::class);

        $key = $this->requestClearKey;
        $action->expects($this->any())->method('getRequest')->willReturnCallback(function () {
            return $this->createRequest();
        });
        $action->expects($this->any())->method('rules')->willReturnCallback(function () use ($key) {
            return [$key => ['integer']];
        });
        $action->expects($this->any())->method('action')->willReturnCallback(function () use ($getParameter) {
            return $getParameter();
        });

        $this->assertSame($getParameter(), $action());
    }

    public function testGetOrSetAttribute()
    {
        $action = $this->getMockBuilder(Action::class)->getMockForAbstractClass();

        $getOrSetAttribute = Closure::bind(function ($name, $callable, $reset = false) {
            /** @var Action $this */
            return $this->getOrSetAttribute($name, $callable, $reset);
        }, $action, Action::class);


        $name = md5(serialize([random_bytes(100), random_int(0, 999999999999)]));
        $value = $getOrSetAttribute($name, function () {
            return random_int(0, 999999999999);
        });
        $this->assertSame($value, $getOrSetAttribute($name, function () {
            return random_int(0, 999999999999);
        }));
        $this->assertSame($value, $getOrSetAttribute($name, function () {
            return random_int(0, 999999999999);
        }));
        $this->assertNotSame($value, $getOrSetAttribute($name, function () {
            return random_int(0, 999999999999);
        }, true));

        $name = md5(serialize([random_bytes(100), random_int(0, 999999999999)]));
        $this->assertSame(null, $getOrSetAttribute($name, function () {
            return null;
        }));
        $this->assertSame(null, $getOrSetAttribute($name, function () {
            return random_int(0, 999999999999);
        }));
        $this->assertSame(null, $getOrSetAttribute($name, function () {
            return false;
        }));
        $this->assertSame(false, $getOrSetAttribute($name, function () {
            return false;
        }, true));
    }

    public function testWebRoute()
    {
        $response = $this->json('POST', "/test", []);
        $response->assertOk();
    }
}
