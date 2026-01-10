<?php

namespace HughCube\Laravel\Knight\Tests\Routing;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;

class ProxyForwarderTest extends TestCase
{
    public function testAction()
    {
        $mock = new MockHandler([
            new GuzzleResponse(200, ['X-Foo' => 'Bar'], 'Hello, World'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $action = new ProxyForwarderAction();
        $action->setClient($client);

        $request = Request::create('/test', 'GET');
        $this->app->instance('request', $request);

        /** @var GuzzleResponse $response */
        $response = $this->callMethod($action, 'action');

        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['Bar'], $response->getHeader('X-Foo'));
    }

    public function testGetProxyRequestOptions()
    {
        $action = new ProxyForwarderAction();
        $request = Request::create('/test', 'GET', [], [], [], [], 'test body');
        $this->app->instance('request', $request);

        $options = $this->callMethod($action, 'getProxyRequestOptions');

        $this->assertArrayHasKey('body', $options);
        $this->assertSame('test body', $options['body']);
        $this->assertFalse($options['verify']);
        $this->assertFalse($options['allow_redirects']);
    }
}
