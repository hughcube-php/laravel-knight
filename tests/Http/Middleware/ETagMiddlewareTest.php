<?php

namespace HughCube\Laravel\Knight\Tests\Http\Middleware;

use HughCube\Laravel\Knight\Http\Middleware\ETagMiddleware;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ETagMiddlewareTest extends TestCase
{
    public function testGetRequestReturnsETagHeader()
    {
        $middleware = new ETagMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('hello world', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertNotNull($response->headers->get('ETag'));
        $this->assertSame('"' . md5('hello world') . '"', $response->headers->get('ETag'));
    }

    public function testMatchingIfNoneMatchReturns304()
    {
        $middleware = new ETagMiddleware();
        $etag = '"' . md5('hello world') . '"';

        $request = Request::create('/test', 'GET');
        $request->headers->set('If-None-Match', $etag);

        $response = $middleware->handle($request, function () {
            return new Response('hello world', 200);
        });

        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testNonMatchingIfNoneMatchReturns200()
    {
        $middleware = new ETagMiddleware();

        $request = Request::create('/test', 'GET');
        $request->headers->set('If-None-Match', '"oldetag"');

        $response = $middleware->handle($request, function () {
            return new Response('hello world', 200);
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('hello world', $response->getContent());
    }

    public function testPostRequestDoesNotAddETag()
    {
        $middleware = new ETagMiddleware();
        $request = Request::create('/test', 'POST');

        $response = $middleware->handle($request, function () {
            return new Response('created', 200);
        });

        $this->assertNull($response->headers->get('ETag'));
    }

    public function testNon200ResponseDoesNotAddETag()
    {
        $middleware = new ETagMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('not found', 404);
        });

        $this->assertNull($response->headers->get('ETag'));
    }

    public function testHeadRequestReturnsETag()
    {
        $middleware = new ETagMiddleware();
        $request = Request::create('/test', 'HEAD');

        $response = $middleware->handle($request, function () {
            return new Response('content', 200);
        });

        $this->assertNotNull($response->headers->get('ETag'));
    }
}
