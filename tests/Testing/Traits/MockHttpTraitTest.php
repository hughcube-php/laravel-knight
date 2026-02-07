<?php

namespace HughCube\Laravel\Knight\Tests\Testing\Traits;

use HughCube\Laravel\Knight\Testing\Traits\MockHttpTrait;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class MockHttpTraitTest extends TestCase
{
    use MockHttpTrait;

    public function testMockHttpResponse()
    {
        $this->mockHttpResponse('https://example.com/api', 200, [], 'hello');

        $response = Http::get('https://example.com/api');

        $this->assertSame(200, $response->status());
        $this->assertSame('hello', $response->body());
    }

    public function testMockHttpJsonResponse()
    {
        $this->mockHttpJsonResponse('https://example.com/api/json', ['status' => 'ok'], 200);

        $response = Http::get('https://example.com/api/json');

        $this->assertSame(200, $response->status());
        $this->assertSame('ok', $response->json('status'));
    }

    public function testGetRecordedHttpRequests()
    {
        Http::fake([
            'https://example.com/*' => Http::response('ok'),
        ]);

        Http::get('https://example.com/first');
        Http::get('https://example.com/second');

        $recorded = $this->getRecordedHttpRequests();

        $this->assertCount(2, $recorded);
    }
}
