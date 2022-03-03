<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\Knight\Tests\OPcache\Actions;

use HughCube\Laravel\Knight\OPcache\Actions\ScriptsAction;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;

/**
 * @requires function opcache_reset
 */
class ScriptsActionTest extends TestCase
{
    public function testRun()
    {
        if (!opcache_reset()) {
            $this->markTestSkipped();
        }

        /** @var ScriptsAction $action */
        $action = $this->app->make(ScriptsAction::class);

        $response = $action();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertJson($response->content());

        $this->assertArrayHasKey('code', $response->getData(true));
        $this->assertSame(200, Arr::get($response->getData(true), 'code'));
        $this->assertIsArray(Arr::get($response->getData(true), 'data.scripts'));
    }
}
