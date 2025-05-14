<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/30
 * Time: 17:36.
 */

namespace HughCube\Laravel\Knight\Tests\Traits;

use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\Laravel\Knight\Traits\MultipleHandler;
use Illuminate\Support\Collection;
use ReflectionMethod;

class MultipleHandlerTest extends TestCase
{
    /**
     * @throws \ReflectionException
     */
    public function testMultipleHandler()
    {
        $job = new class() {
            use MultipleHandler;

            public $doneHandlers = [];

            protected function aHandler10000()
            {
                $this->doneHandlers[] = __FUNCTION__;
            }

            protected function bHandler()
            {
                $this->doneHandlers[] = __FUNCTION__;
            }

            protected function cHandler100()
            {
                $this->doneHandlers[] = __FUNCTION__;
            }

            protected function dHandlerA()
            {
                $this->doneHandlers[] = __FUNCTION__;
            }
        };

        $this->assertSame(
            Collection::make($this->callMethod($job, 'getMultipleHandlers'))
                ->map(function (ReflectionMethod $method) {
                    return $method->name;
                })
                ->values()
                ->toArray(),
            ['bHandler', 'cHandler100', 'aHandler10000']
        );

        $this->assertEmpty($this->getProperty($job, 'doneHandlers'));
        $this->callMethod($job, 'triggerMultipleHandlers');
        $this->assertSame($this->getProperty($job, 'doneHandlers'), ['bHandler', 'cHandler100', 'aHandler10000']);
    }
}
