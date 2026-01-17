<?php

namespace HughCube\Laravel\Knight\Tests\Events;

use HughCube\Laravel\Knight\Events\ActionProcessed;
use HughCube\Laravel\Knight\Events\ActionProcessing;
use HughCube\Laravel\Knight\Tests\TestCase;
use stdClass;

class ActionEventTest extends TestCase
{
    public function testActionProcessingStoresAction()
    {
        $action = new stdClass();
        $event = new ActionProcessing($action);

        $this->assertSame($action, self::callMethod($event, 'getAction'));
    }

    public function testActionProcessedStoresAction()
    {
        $action = new stdClass();
        $event = new ActionProcessed($action);

        $this->assertSame($action, self::callMethod($event, 'getAction'));
    }
}
