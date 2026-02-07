<?php

namespace HughCube\Laravel\Knight\Tests\Mixin\Http;

use HughCube\Laravel\Knight\Mixin\Http\RequestMixin;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Http\Request;

class RequestMixinDeviceDetectTest extends TestCase
{
    protected function createRequestWithUA(string $userAgent): Request
    {
        return Request::create('/test', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $userAgent,
        ]);
    }

    public function testIsWeCom()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 wxwork/3.0');
        $this->assertTrue($request->isWeCom());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isWeCom());
    }

    public function testIsDingTalk()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 DingTalk/6.0');
        $this->assertTrue($request->isDingTalk());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isDingTalk());
    }

    public function testIsFeishu()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 Lark/5.0');
        $this->assertTrue($request->isFeishu());

        $request = $this->createRequestWithUA('Mozilla/5.0 Feishu/5.0');
        $this->assertTrue($request->isFeishu());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isFeishu());
    }

    public function testIsAlipay()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 AlipayClient/10.0');
        $this->assertTrue($request->isAlipay());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isAlipay());
    }

    public function testIsQQ()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 QQ/8.0');
        $this->assertTrue($request->isQQ());

        // QQ浏览器不是QQ客户端
        $request = $this->createRequestWithUA('Mozilla/5.0 MQQBrowser/11.0 QQ/8.0');
        $this->assertFalse($request->isQQ());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isQQ());
    }

    public function testIsQQBrowser()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 MQQBrowser/11.0');
        $this->assertTrue($request->isQQBrowser());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isQQBrowser());
    }

    public function testIsUCBrowser()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 UCBrowser/13.0');
        $this->assertTrue($request->isUCBrowser());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isUCBrowser());
    }

    public function testIsWeibo()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 Weibo/12.0');
        $this->assertTrue($request->isWeibo());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isWeibo());
    }

    public function testIsDouyin()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 Aweme/18.0');
        $this->assertTrue($request->isDouyin());

        $request = $this->createRequestWithUA('Mozilla/5.0 BytedanceWebview/d8a21c6');
        $this->assertTrue($request->isDouyin());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isDouyin());
    }

    public function testIsQuark()
    {
        $request = $this->createRequestWithUA('Mozilla/5.0 Quark/5.0');
        $this->assertTrue($request->isQuark());

        $request = $this->createRequestWithUA('Mozilla/5.0 Chrome/91.0');
        $this->assertFalse($request->isQuark());
    }
}
