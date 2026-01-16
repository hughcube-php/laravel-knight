<?php

namespace HughCube\Laravel\Knight\Tests\Http\Actions;

use HughCube\Laravel\Knight\Http\Actions\NowAction;
use HughCube\Laravel\Knight\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class NowActionTest extends TestCase
{
    public function testActionReturnsTimestampAndRfc3339()
    {
        $action = new NowAction();
        $response = $this->callMethod($action, 'action');

        $this->assertInstanceOf(Response::class, $response);

        $data = json_decode($response->getContent(), true);

        $this->assertSame('Success', $data['Code']);
        $this->assertArrayHasKey('timestamp', $data['Data']);
        $this->assertArrayHasKey('rfc3339', $data['Data']);
    }
}
