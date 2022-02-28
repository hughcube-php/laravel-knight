<?php

namespace HughCube\Laravel\Knight\Tests\Http;

use HughCube\Laravel\Knight\Http\LaravelRequest;
use HughCube\Laravel\Knight\Tests\TestCase;

class LaravelRequestTest extends TestCase
{
    public function testMake()
    {
        $exception = null;

        try {
            LaravelRequest::capture();
        } catch (\Throwable $exception) {
        }

        $this->assertNull($exception);
    }
}
