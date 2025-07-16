<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\Knight\Tests\OPcache\Actions;

use HughCube\Laravel\Knight\OPcache\Actions\StatesAction;
use HughCube\Laravel\Knight\Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @requires function opcache_reset
 */
class StatesActionTest extends TestCase
{
    /**
     * @return void
     */
    public function testRun()
    {
        if (!extension_loaded('Zend OPcache') || !function_exists('opcache_reset')) {
            $this->markTestSkipped('OPcache extension is not loaded or opcache_reset function is not available');
            return;
        }

        if (!opcache_reset()) {
            $this->markTestSkipped('Failed to reset OPcache');
            return;
        }

        /** @var StatesAction $action */
        $action = $this->app->make(StatesAction::class);

        $response = $action();
        $this->assertInstanceOf(Response::class, $response);
    }
}
