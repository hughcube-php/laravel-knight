<?php

namespace HughCube\Laravel\Knight\Tests\Http;

use HughCube\Laravel\Knight\Http\LumenRequest;
use HughCube\Laravel\Knight\Tests\TestCase;

class LumenRequestTest extends TestCase
{
    public function testMake()
    {
        $exception = null;
        try {
            LumenRequest::capture();
        } catch (\Throwable $exception) {
        }

        $this->assertNull($exception);
    }
}
