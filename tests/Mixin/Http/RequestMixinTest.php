<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:50.
 */

namespace HughCube\Laravel\Knight\Tests\Mixin\Http;

use HughCube\Laravel\Knight\Mixin\Http\RequestMixin;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

/**
 * @see RequestMixin
 */
class RequestMixinTest extends TestCase
{
    /**
     * @see RequestMixin::getUserAgentDetect()
     */
    public function testGetUserAgentDetect()
    {
        $request = Request::capture();

        $this->assertInstanceOf(Agent::class, $agent = $request->getUserAgentDetect());
        $this->assertSame($request->getUserAgentDetect(), $agent);
    }

    /**
     * @see RequestMixin::isWeChat()
     */
    public function testIsWeChat()
    {
        $this->assertFalse(Request::capture()->isWeChat());
    }

    /**
     * @see RequestMixin::isWeChatMiniProgram()
     */
    public function testIsWeChatMiniProgram()
    {
        $this->assertFalse(Request::capture()->isWeChatMiniProgram());
    }

    /**
     * @see RequestMixin::isEqClientVersion()
     */
    public function testIsEqClientVersion()
    {
        $request = Request::capture();
        Request::macro('getClientVersion', function () {
            return '1.0.0';
        });
        $this->assertTrue($request->isEqClientVersion('1', 1));
        $this->assertTrue($request->isEqClientVersion('1.0', 2));
        $this->assertTrue($request->isEqClientVersion('1.0.0', 3));
        $this->assertTrue($request->isEqClientVersion('1.0.0'));
        $this->assertFalse($request->isEqClientVersion('2.0.0'));
    }

    /**
     * @see RequestMixin::isLtClientVersion()
     */
    public function testIsLtClientApiVersion()
    {
        $request = Request::capture();
        Request::macro('getClientVersion', function () {
            return '2.2.2';
        });
        $this->assertTrue($request->isLtClientVersion('2', true));
        $this->assertTrue($request->isLtClientVersion('2'));

        $this->assertTrue($request->isLtClientVersion('2.2', true));
        $this->assertTrue($request->isLtClientVersion('2.1'));

        $this->assertTrue($request->isLtClientVersion('2.2.2', true));
        $this->assertTrue($request->isLtClientVersion('2.2.1'));

        $this->assertTrue($request->isLtClientVersion('2.2.2', true));

        $this->assertFalse($request->isLtClientVersion('3.0.0'));
    }

    /**
     * @see RequestMixin::isGtClientVersion()
     */
    public function testIsLtClientVersion()
    {
        $request = Request::capture();
        Request::macro('getClientVersion', function () {
            return '2.2.2';
        });
        $this->assertTrue($request->isGtClientVersion('3', true));
        $this->assertTrue($request->isGtClientVersion('3'));

        $this->assertTrue($request->isGtClientVersion('3.2', true));
        $this->assertTrue($request->isGtClientVersion('3.1'));

        $this->assertTrue($request->isGtClientVersion('3.2.2', true));
        $this->assertTrue($request->isGtClientVersion('3.2.1'));

        $this->assertTrue($request->isGtClientVersion('3.2.2', true));

        $this->assertFalse($request->isGtClientVersion('2.0.0'));
    }
}
