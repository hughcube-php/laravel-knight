<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 7:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Routing;

use BadMethodCallException;
use Closure;
use Dotenv\Exception\ValidationException;
use Exception;
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use ReflectionException;

class ActionTest extends TestCase
{
    protected function defineWebRoutes($router)
    {
        $router->POST('/test', Action::class);
    }

    /**
     * @requires PHP >= 7.2
     *
     * @throws Exception
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

    /**
     * @throws ReflectionException
     */
    public function testGetRequest()
    {
        $action = new Action();

        $this->assertInstanceOf(Request::class, $this->callMethod($action, 'getRequest'));
    }

    /**
     * @covers \HughCube\Laravel\Knight\Routing\Action::rules
     *
     * @throws Exception
     */
    public function testGetParameter()
    {
        $action = new Action();

        $uuid = md5(random_bytes(100));
        $data = ['uuid' => $uuid, 'uuid2' => md5(random_bytes(100))];

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $this->app->instance('request', $request);

        $parameter = $this->callMethod($action, 'p');
        $this->assertInstanceOf(ParameterBag::class, $parameter);

        $this->assertTrue($parameter->has('uuid'));
        $this->assertSame($parameter->get('uuid'), $request->json('uuid'));

        $this->assertTrue($action->has('uuid'));
        $this->assertSame($action->get('uuid'), $request->json('uuid'));

        if (version_compare(PHP_VERSION, '7.1', '>=')) {
            $this->assertFalse($parameter->has('uuid2'));
            $this->assertSame(1, $parameter->count());
        }
    }

    /**
     * @throws ValidationException
     * @throws Exception
     */
    public function testInvoke()
    {
        $action = new Action();

        $uuid = md5(random_bytes(100));
        $data = ['uuid' => $uuid, 'uuid2' => md5(random_bytes(100))];

        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data)
        );
        $this->app->instance('request', $request);

        $this->assertSame($action(), $this->callMethod($action, 'action'));
        $this->assertSame($action()['uuid'], $uuid);
    }

    /**
     * @throws Exception
     */
    public function testGetOrSetAttribute()
    {
        $action = new Action();

        $name = md5(serialize([random_bytes(100), random_int(0, 999999999999)]));
        $value = $this->callMethod($action, 'getOrSet', [
            $name,
            function () {
                return random_int(0, 999999999999);
            },
        ]);

        $this->assertSame($value, $this->callMethod($action, 'getOrSet', [
            $name,
            function () {
                return random_int(0, 999999999999);
            },
        ]));

        $count = 0;
        $name = md5(serialize([random_bytes(100), random_int(0, 999999999999)]));
        $this->assertSame(null, $this->callMethod($action, 'getOrSet', [
            $name,
            function () use (&$count) {
                $count++;

                return null;
            },
        ]));
        $this->assertSame(null, $this->callMethod($action, 'getOrSet', [
            $name,
            function () use (&$count) {
                $count++;

                return null;
            },
        ]));
        $this->assertSame(1, $count);
    }

    public function testAsSuccess()
    {
        $action = new Action();
        $data = ['key' => 'value'];

        $response = $this->callMethod($action, 'asSuccess', [$data]);

        $this->assertInstanceOf(\HughCube\Laravel\Knight\Http\JsonResponse::class, $response);
        $content = $response->getData(true);
        $this->assertSame('Success', $content['Code']);
        $this->assertSame('Success', $content['Message']);
        $this->assertSame($data, $content['Data']);
    }

    public function testAsFailure()
    {
        $action = new Action();
        $data = ['error' => 'details'];
        $code = 'Error code';
        $message = 'Error message';

        $response = $this->callMethod($action, 'asFailure', [$code, $message, $data]);

        $this->assertInstanceOf(\HughCube\Laravel\Knight\Http\JsonResponse::class, $response);
        $content = $response->getData(true);
        $this->assertSame($code, $content['Code']);
        $this->assertSame($message, $content['Message']);
        $this->assertSame($data, $content['Data']);
    }

    public function testMagicGetAndCall()
    {
        $action = new Action();

        $uuid = md5(random_bytes(100));
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uuid' => $uuid])
        );
        $this->app->instance('request', $request);

        $this->assertSame($uuid, $action->uuid);

        $this->expectException(BadMethodCallException::class);
        $action->missingMethod();
    }

    /**
     * @throws Exception
     */
    public function testActionMiddlewaresWithNoMiddleware()
    {
        ActionWithMiddleware::resetExecutionLog();

        $action = new ActionWithMiddleware();
        $action->setMiddlewares([]);

        $uuid = md5(random_bytes(100));
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uuid' => $uuid])
        );
        $this->app->instance('request', $request);

        $result = $action();

        $this->assertSame($uuid, $result['uuid']);
        $this->assertSame(['action'], ActionWithMiddleware::$middlewareExecutionLog);
    }

    /**
     * @throws Exception
     */
    public function testActionMiddlewaresWithSingleMiddleware()
    {
        ActionWithMiddleware::resetExecutionLog();

        $action = new ActionWithMiddleware();
        $action->setMiddlewares([
            new TestMiddleware('first'),
        ]);

        $uuid = md5(random_bytes(100));
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uuid' => $uuid])
        );
        $this->app->instance('request', $request);

        $result = $action();

        $this->assertSame($uuid, $result['uuid']);
        $this->assertSame([
            'middleware:first:before',
            'action',
            'middleware:first:after',
        ], ActionWithMiddleware::$middlewareExecutionLog);
    }

    /**
     * @throws Exception
     */
    public function testActionMiddlewaresWithMultipleMiddlewares()
    {
        ActionWithMiddleware::resetExecutionLog();

        $action = new ActionWithMiddleware();
        $action->setMiddlewares([
            new TestMiddleware('first'),
            new TestMiddleware('second'),
            new TestMiddleware('third'),
        ]);

        $uuid = md5(random_bytes(100));
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uuid' => $uuid])
        );
        $this->app->instance('request', $request);

        $result = $action();

        $this->assertSame($uuid, $result['uuid']);
        $this->assertSame([
            'middleware:first:before',
            'middleware:second:before',
            'middleware:third:before',
            'action',
            'middleware:third:after',
            'middleware:second:after',
            'middleware:first:after',
        ], ActionWithMiddleware::$middlewareExecutionLog);
    }

    /**
     * @throws Exception
     */
    public function testActionMiddlewaresWithClosureMiddleware()
    {
        ActionWithMiddleware::resetExecutionLog();

        $action = new ActionWithMiddleware();
        $action->setMiddlewares([
            function (Request $request, Closure $next) {
                ActionWithMiddleware::$middlewareExecutionLog[] = 'closure:before';
                $response = $next($request);
                ActionWithMiddleware::$middlewareExecutionLog[] = 'closure:after';

                return $response;
            },
        ]);

        $uuid = md5(random_bytes(100));
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uuid' => $uuid])
        );
        $this->app->instance('request', $request);

        $result = $action();

        $this->assertSame($uuid, $result['uuid']);
        $this->assertSame([
            'closure:before',
            'action',
            'closure:after',
        ], ActionWithMiddleware::$middlewareExecutionLog);
    }

    /**
     * @throws Exception
     */
    public function testActionMiddlewaresWithMixedMiddlewares()
    {
        ActionWithMiddleware::resetExecutionLog();

        $action = new ActionWithMiddleware();
        $action->setMiddlewares([
            new TestMiddleware('class'),
            function (Request $request, Closure $next) {
                ActionWithMiddleware::$middlewareExecutionLog[] = 'closure:before';
                $response = $next($request);
                ActionWithMiddleware::$middlewareExecutionLog[] = 'closure:after';

                return $response;
            },
        ]);

        $uuid = md5(random_bytes(100));
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uuid' => $uuid])
        );
        $this->app->instance('request', $request);

        $result = $action();

        $this->assertSame($uuid, $result['uuid']);
        $this->assertSame([
            'middleware:class:before',
            'closure:before',
            'action',
            'closure:after',
            'middleware:class:after',
        ], ActionWithMiddleware::$middlewareExecutionLog);
    }

    /**
     * @throws Exception
     */
    public function testActionMiddlewaresCanModifyRequest()
    {
        ActionWithMiddleware::resetExecutionLog();

        $action = new ActionWithMiddleware();
        $action->setMiddlewares([
            function (Request $request, Closure $next) {
                $request->attributes->set('middleware_data', 'modified');

                return $next($request);
            },
        ]);

        $uuid = md5(random_bytes(100));
        $request = Request::create(
            '/test',
            'GET',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['uuid' => $uuid])
        );
        $this->app->instance('request', $request);

        $action();

        $this->assertSame('modified', $request->attributes->get('middleware_data'));
    }
}
