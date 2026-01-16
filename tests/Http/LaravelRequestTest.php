<?php

namespace HughCube\Laravel\Knight\Tests\Http;

use HughCube\Laravel\Knight\Http\LaravelRequest;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request as IlluminateRequest;

class LaravelRequestTest extends TestCase
{
    public function testCreateReturnsLaravelRequest()
    {
        $request = LaravelRequest::create('/hello', 'GET');

        $this->assertInstanceOf(IlluminateRequest::class, $request);
        $this->assertSame('hello', $request->path());
    }
}
