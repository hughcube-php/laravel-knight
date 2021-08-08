<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 7:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Routing;

use Dotenv\Exception\ValidationException;
use Exception;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use ReflectionException;
use Symfony\Component\HttpFoundation\ParameterBag;

class ActionTest extends TestCase
{
    protected function defineWebRoutes($router)
    {
        $router->POST('/test', Action::class);
    }

    /**
     * @requires PHP >= 7.2
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
        $this->setProperty($action, 'request', $request);

        $parameter = $this->callMethod($action, 'getParameter');
        $this->assertInstanceOf(ParameterBag::class, $parameter);

        $this->assertTrue($parameter->has('uuid'));
        $this->assertSame($parameter->get('uuid'), $request->json('uuid'));

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
        $this->setProperty($action, 'request', $request);

        $this->assertSame($action(), $action->action());
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
            }
        ]);

        $this->assertSame($value, $this->callMethod($action, 'getOrSet', [
            $name,
            function () {
                return random_int(0, 999999999999);
            }
        ]));

        $count = 0;
        $name = md5(serialize([random_bytes(100), random_int(0, 999999999999)]));
        $this->assertSame(null, $this->callMethod($action, 'getOrSet', [
            $name,
            function () use (&$count) {
                $count++;
                return null;
            }
        ]));
        $this->assertSame(null, $this->callMethod($action, 'getOrSet', [
            $name,
            function () use (&$count) {
                $count++;
                return null;
            }
        ]));
        $this->assertSame(1, $count);
    }
}
