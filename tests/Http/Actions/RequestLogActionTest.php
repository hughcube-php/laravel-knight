<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36.
 */

namespace HughCube\Laravel\Knight\Tests\Http\Actions;

use HughCube\Laravel\Knight\Http\Actions\RequestLogAction;
use HughCube\Laravel\Knight\Tests\TestCase;

class RequestLogActionTest extends TestCase
{
    /**
     * @return void
     */
    public function testRun()
    {
        /** @var RequestLogAction $action */
        $action = $this->app->make(RequestLogAction::class);

        $this->assertSame($action(), 'success');
    }
}
