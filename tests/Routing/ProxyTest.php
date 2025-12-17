<?php

namespace HughCube\Laravel\Knight\Tests\Routing;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProxyTest extends TestCase
{
    public function testAction()
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, ['X-Foo' => 'Bar'], 'Hello, World'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $action = new ProxyAction();
        $action->setClient($client);

        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        /** @var StreamedResponse $response */
        $response = $this->callMethod($action, 'action');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Bar', $response->headers->get('X-Foo'));

        $this->expectOutputString('Hello, World');
        $response->sendContent();
    }
}