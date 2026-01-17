<?php

namespace HughCube\Laravel\Knight\Tests\Http\Actions;

use HughCube\Laravel\Knight\Http\Actions\PhpInfoAction;
use HughCube\Laravel\Knight\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class PhpInfoActionTest extends TestCase
{
    public function testActionWrapsPhpInfoOutput()
    {
        $action = new PhpInfoAction();
        $response = $this->callMethod($action, 'action');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue(str_starts_with($response->getContent(), '<'));
    }
}
