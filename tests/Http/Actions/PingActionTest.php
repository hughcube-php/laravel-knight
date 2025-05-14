<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36.
 */

namespace HughCube\Laravel\Knight\Tests\Http\Actions;

use HughCube\Laravel\Knight\Http\Actions\PingAction;
use HughCube\Laravel\Knight\Tests\TestCase;

class PingActionTest extends TestCase
{
    /**
     * @return void
     */
    public function testRun()
    {
        /** @var PingAction $action */
        $action = $this->app->make(PingAction::class);

        $this->assertSame($action(), 'done');
    }
}
