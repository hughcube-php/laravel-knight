<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use EasyWeChat\MiniApp\Application as MiniApp;
use EasyWeChat\OfficialAccount\Application as OfficialAccount;
use HughCube\Laravel\Knight\Queue\Jobs\RefreshWeChatMiniAppAccessTokensJob;
use HughCube\Laravel\Knight\Queue\Jobs\RefreshWeChatOfficialAccountAccessTokensJob;
use HughCube\Laravel\Knight\Tests\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RefreshWeChatAccessTokensJobTest extends TestCase
{
    public function testMiniAppResetHttpClientSkipsWhenNoProxy()
    {
        if (!class_exists(MiniApp::class)) {
            $this->markTestSkipped('MiniApp Application class is not available.');
        }

        $job = new RefreshWeChatMiniAppAccessTokensJob(['proxy' => null]);
        $this->callMethod($job, 'loadParameters');

        $app = $this->getMockBuilder(MiniApp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHttpClient', 'setHttpClient'])
            ->getMock();

        $app->expects($this->never())->method('getHttpClient');
        $app->expects($this->never())->method('setHttpClient');

        $result = $this->callMethod($job, 'resetHttpClient', [$app]);

        $this->assertSame($app, $result);
    }

    public function testMiniAppResetHttpClientUsesProxy()
    {
        if (!class_exists(MiniApp::class)) {
            $this->markTestSkipped('MiniApp Application class is not available.');
        }

        $job = new RefreshWeChatMiniAppAccessTokensJob(['proxy' => 'http://proxy']);
        $this->callMethod($job, 'loadParameters');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('withOptions')
            ->with(['proxy' => 'http://proxy'])
            ->willReturn($httpClient);

        $app = $this->getMockBuilder(MiniApp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHttpClient', 'setHttpClient'])
            ->getMock();

        $app->expects($this->once())->method('getHttpClient')->willReturn($httpClient);
        $app->expects($this->once())->method('setHttpClient')->with($httpClient)->willReturn($app);

        $result = $this->callMethod($job, 'resetHttpClient', [$app]);

        $this->assertSame($app, $result);
    }

    public function testOfficialAccountResetHttpClientUsesProxy()
    {
        if (!class_exists(OfficialAccount::class)) {
            $this->markTestSkipped('OfficialAccount Application class is not available.');
        }

        $job = new RefreshWeChatOfficialAccountAccessTokensJob(['proxy' => 'http://proxy']);
        $this->callMethod($job, 'loadParameters');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('withOptions')
            ->with(['proxy' => 'http://proxy'])
            ->willReturn($httpClient);

        $app = $this->getMockBuilder(OfficialAccount::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHttpClient', 'setHttpClient'])
            ->getMock();

        $app->expects($this->once())->method('getHttpClient')->willReturn($httpClient);
        $app->expects($this->once())->method('setHttpClient')->with($httpClient)->willReturn($app);

        $result = $this->callMethod($job, 'resetHttpClient', [$app]);

        $this->assertSame($app, $result);
    }
}
