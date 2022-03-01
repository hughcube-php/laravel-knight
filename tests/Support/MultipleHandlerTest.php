<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/1
 * Time: 14:35
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\MultipleHandler;
use HughCube\Laravel\Knight\Tests\TestCase;

class MultipleHandlerTest extends TestCase
{
    public function testMake()
    {
        $this->assertNoException(function () {
            $instance = new class {
                use MultipleHandler;
            };

            $this->callMethod($instance, 'isStopHandlerResults', [true]);
            $this->callMethod($instance, 'triggerHandlers');
            $this->callMethod($instance, 'getHandlers');
        });
    }
}
