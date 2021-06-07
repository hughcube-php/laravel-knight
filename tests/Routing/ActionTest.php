<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 7:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Routing;

use Closure;
use HughCube\Laravel\Knight\Http\ParameterBag;
use HughCube\Laravel\Knight\Routing\Action;
use HughCube\Laravel\Knight\Tests\Routing\Action as TestAction;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request as IlluminateRequest;

class ActionTest extends TestCase
{
    protected function defineWebRoutes($router)
    {
        $router->POST('/test', TestAction::class);
    }

    /**
     * @requires PHP >= 7.2
     */
    public function testWebRoute()
    {
        $uuid = md5(random_bytes(100));
        $response = $this->json('POST', '/test', [
            'uuid' => $uuid,
            'test' => md5(random_bytes(100)),
        ]);

        $this->assertSame($response->getStatusCode(), 200);
        $this->assertSame($response->content(), "{\"uuid\":\"$uuid\"}");
    }

    public function testGetRequest()
    {
        /** @var TestAction $action */
        $action = new TestAction();

        $getRequest = Closure::bind(function () {
            /** @var Action $this */
            return $this->getRequest();
        }, $action, Action::class);

        $this->assertInstanceOf(IlluminateRequest::class, $getRequest());
    }

    /**
     * @covers \HughCube\Laravel\Knight\Routing\Action::rules
     *
     * @throws \Exception
     */
    public function testGetParameter()
    {
        /** @var TestAction $action */
        $action = new TestAction();

        $uuid = md5(random_bytes(100));
        $data = ['uuid' => $uuid, 'uuid2' => md5(random_bytes(100))];

        $getParameter = Closure::bind(function ($key = null) {
            /** @var Action $this */
            $this->loadParameters();

            return $this->parameter($key);
        }, $action, Action::class);

        $setRequest = Closure::bind(function ($request) {
            $this->request = $request;
        }, $action, Action::class);
        $setRequest($request = IlluminateRequest::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        ));

        $this->assertInstanceOf(ParameterBag::class, $getParameter());
        $this->assertTrue($getParameter()->has('uuid'));
        $this->assertSame($getParameter()->get('uuid'), $request->json('uuid'));

        if (version_compare(PHP_VERSION, '7.1', '>=')) {
            $this->assertFalse($getParameter()->has('uuid2'));
            $this->assertSame(1, count($getParameter()->all()));
        }
    }

    public function testInvoke()
    {
        /** @var TestAction $action */
        $action = new TestAction();

        $uuid = md5(random_bytes(100));
        $data = ['uuid' => $uuid, 'uuid2' => md5(random_bytes(100))];

        $callAction = Closure::bind(function () {
            /** @var Action $this */
            return $this->action();
        }, $action, Action::class);

        $setRequest = Closure::bind(function ($request) {
            $this->request = $request;
        }, $action, Action::class);
        $setRequest($request = IlluminateRequest::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        ));

        $this->assertSame($action(), $callAction());
        $this->assertSame($action()['uuid'], $uuid);
    }

    public function testGetOrSetAttribute()
    {
        /** @var TestAction $action */
        $action = new TestAction();

        $getOrSetAttribute = Closure::bind(function ($name, $callable, $reset = false) {
            /** @var Action $this */
            return $this->getOrSet($name, $callable, $reset);
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
}
