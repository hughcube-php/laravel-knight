<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:50.
 */

namespace HughCube\Laravel\Knight\Tests\Http;

use HughCube\Laravel\Knight\Mixin\Http\RequestMixin;
use HughCube\Laravel\Knight\Tests\TestCase;
use Jenssegers\Agent\Agent;
use HughCube\Laravel\Knight\Http\Request;

/**
 * @see RequestMixin
 */
class RequestTest extends TestCase
{
    /**
     * @see Request::getUserAgentDetect()
     */
    public function testGetUserAgentDetect()
    {
        $request = Request::capture();

        $this->assertInstanceOf(Agent::class, $agent = $request->getUserAgentDetect());
        $this->assertSame($request->getUserAgentDetect(), $agent);
    }

    /**
     * @see Request::isWeChat()
     */
    public function testIsWeChat()
    {
        $this->assertFalse(Request::capture()->isWeChat());
    }

    /**
     * @see Request::isWeChatMiniProgram()
     */
    public function testIsWeChatMiniProgram()
    {
        $this->assertFalse(Request::capture()->isWeChatMiniProgram());
    }

    /**
     * @see Request::isEqClientVersion()
     */
    public function testIsEqClientVersion()
    {
        $request = Request::capture();
        $request->headers->set('Version', '1.0.0');

        $this->assertTrue($request->isEqClientVersion('1', 1));
        $this->assertTrue($request->isEqClientVersion('1.0', 2));
        $this->assertTrue($request->isEqClientVersion('1.0.0', 3));
        $this->assertTrue($request->isEqClientVersion('1.0.0'));
        $this->assertFalse($request->isEqClientVersion('2.0.0'));
    }

    /**
     * @see Request::isLtClientVersion()
     */
    public function testIsLtClientApiVersion()
    {
        $request = Request::capture();
        $request->headers->set('Version', '2.2.2');

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
     * @see Request::isGtClientVersion()
     */
    public function testIsLtClientVersion()
    {
        $request = Request::capture();

        $request->headers->set('Version', '2.2.2');

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
