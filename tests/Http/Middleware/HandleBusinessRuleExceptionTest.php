<?php

namespace HughCube\Laravel\Knight\Tests\Http\Middleware;

use HughCube\Laravel\Knight\Exceptions\BusinessRuleException;
use HughCube\Laravel\Knight\Exceptions\UserException;
use HughCube\Laravel\Knight\Http\Middleware\HandleBusinessRuleException;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleBusinessRuleExceptionTest extends TestCase
{
    public function testPassesThroughWhenNoException()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertSame('ok', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConvertsBusinessRuleExceptionToUserException()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'POST');

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('余额不足');

        $middleware->handle($request, function () {
            throw new BusinessRuleException('余额不足');
        });
    }

    public function testPreservesExceptionMessage()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');
        $message = '订单已过期，无法支付';

        try {
            $middleware->handle($request, function () use ($message) {
                throw new BusinessRuleException($message);
            });
            $this->fail('应该抛出 UserException');
        } catch (UserException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    public function testPreservesExceptionCode()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        try {
            $middleware->handle($request, function () {
                throw new BusinessRuleException('错误', 40001);
            });
            $this->fail('应该抛出 UserException');
        } catch (UserException $e) {
            $this->assertSame(40001, $e->getCode());
        }
    }

    public function testOriginalExceptionIsPrevious()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        try {
            $middleware->handle($request, function () {
                throw new BusinessRuleException('业务异常', 500);
            });
            $this->fail('应该抛出 UserException');
        } catch (UserException $e) {
            $previous = $e->getPrevious();
            $this->assertInstanceOf(BusinessRuleException::class, $previous);
            $this->assertSame('业务异常', $previous->getMessage());
            $this->assertSame(500, $previous->getCode());
        }
    }

    public function testDoesNotCatchOtherExceptions()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('参数错误');

        $middleware->handle($request, function () {
            throw new \InvalidArgumentException('参数错误');
        });
    }

    public function testDoesNotCatchRuntimeException()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('运行时错误');

        $middleware->handle($request, function () {
            throw new \RuntimeException('运行时错误');
        });
    }

    public function testDoesNotCatchUserException()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        $this->expectException(UserException::class);
        $this->expectExceptionMessage('用户异常');

        $middleware->handle($request, function () {
            throw new UserException('用户异常');
        });
    }

    public function testReturnsResponseFromNext()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        $expectedResponse = new Response('custom content', 201, ['X-Custom' => 'value']);

        $response = $middleware->handle($request, function () use ($expectedResponse) {
            return $expectedResponse;
        });

        $this->assertSame($expectedResponse, $response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('value', $response->headers->get('X-Custom'));
    }

    public function testHandlesEmptyMessage()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        try {
            $middleware->handle($request, function () {
                throw new BusinessRuleException('');
            });
            $this->fail('应该抛出 UserException');
        } catch (UserException $e) {
            $this->assertSame('', $e->getMessage());
            $this->assertSame(0, $e->getCode());
        }
    }

    public function testHandlesZeroCode()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        try {
            $middleware->handle($request, function () {
                throw new BusinessRuleException('错误', 0);
            });
            $this->fail('应该抛出 UserException');
        } catch (UserException $e) {
            $this->assertSame(0, $e->getCode());
        }
    }

    public function testWorksWithDifferentHttpMethods()
    {
        $middleware = new HandleBusinessRuleException();
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            $request = Request::create('/test', $method);
            $response = $middleware->handle($request, function () {
                return new Response('ok');
            });

            $this->assertSame('ok', $response->getContent(), "Failed for HTTP method: {$method}");
        }
    }

    public function testConversionChainWithNestedPrevious()
    {
        $middleware = new HandleBusinessRuleException();
        $request = Request::create('/test', 'GET');

        $root = new \LogicException('根本原因');
        $business = new BusinessRuleException('业务错误', 123, $root);

        try {
            $middleware->handle($request, function () use ($business) {
                throw $business;
            });
            $this->fail('应该抛出 UserException');
        } catch (UserException $e) {
            $this->assertSame('业务错误', $e->getMessage());
            $this->assertSame(123, $e->getCode());

            $previous = $e->getPrevious();
            $this->assertInstanceOf(BusinessRuleException::class, $previous);
            $this->assertSame($business, $previous);

            $this->assertInstanceOf(\LogicException::class, $previous->getPrevious());
            $this->assertSame('根本原因', $previous->getPrevious()->getMessage());
        }
    }
}
