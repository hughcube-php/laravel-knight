<?php

namespace HughCube\Laravel\Knight\Tests\Exceptions;

use HughCube\Laravel\Knight\Exceptions\Handler;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Contracts\Debug\ExceptionHandler;

class HandlerTest extends TestCase
{
    public function testMake()
    {
        $handler = $this->app->make(Handler::class);

        $this->assertInstanceOf(ExceptionHandler::class, $handler);
    }
}
