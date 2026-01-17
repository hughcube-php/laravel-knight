<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\Knight\Tests\OPcache\Actions;

use HughCube\Laravel\Knight\OPcache\Actions\StatesAction;
use HughCube\Laravel\Knight\Exceptions\UserException;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
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

    public function testRulesContainAsJson()
    {
        $action = new StatesAction();

        $rules = $this->callMethod($action, 'rules');

        $this->assertArrayHasKey('as_json', $rules);
        $this->assertContains('boolean', $rules['as_json']);
    }

    public function testIsAsJsonReadsParameters()
    {
        $request = Request::create('/opcache/states', 'GET', ['as_json' => 1]);
        $this->app->instance('request', $request);

        $action = $this->app->make(StatesAction::class);
        $this->assertTrue($this->callMethod($action, 'isAsJson'));

        $request = Request::create('/opcache/states', 'GET', ['as_json' => 0]);
        $this->app->instance('request', $request);

        $action = $this->app->make(StatesAction::class);
        $this->assertFalse($this->callMethod($action, 'isAsJson'));
    }

    public function testRenderViewReturnsContent()
    {
        $action = new StatesAction();
        $file = tempnam(sys_get_temp_dir(), 'opcache-view');

        file_put_contents($file, '<?php echo "ok";');

        try {
            $output = $this->callMethod($action, 'renderView', [$file]);
            $this->assertSame('ok', $output);
        } finally {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testActionThrowsWhenOpcacheDisabled()
    {
        $action = $this->app->make(StatesAction::class);
        $request = Request::create('/opcache/states', 'GET');
        $this->app->instance('request', $request);

        if (!extension_loaded('Zend OPcache') || !function_exists('opcache_get_status')) {
            $this->expectException(UserException::class);
            $this->callMethod($action, 'action');
            return;
        }

        if (false === @opcache_get_status()) {
            $this->expectException(UserException::class);
            $this->callMethod($action, 'action');
            return;
        }

        $response = $this->callMethod($action, 'action');
        $this->assertInstanceOf(Response::class, $response);
    }
}
