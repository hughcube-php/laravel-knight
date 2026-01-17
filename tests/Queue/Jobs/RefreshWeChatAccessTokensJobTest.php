<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use EasyWeChat\MiniApp\Application as MiniApp;
use EasyWeChat\MiniApp\Contracts\Account as MiniAppAccount;
use EasyWeChat\OfficialAccount\Application as OfficialAccount;
use EasyWeChat\OfficialAccount\Contracts\Account as OfficialAccountAccount;
use EasyWeChat\Kernel\Contracts\AccessToken as AccessTokenInterface;
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

    public function testMiniAppActionRefreshesTokensAndSkipsEmptyAppId()
    {
        if (!class_exists(MiniApp::class)) {
            $this->markTestSkipped('MiniApp Application class is not available.');
        }

        config(['easywechat.mini_app' => ['empty' => [], 'valid' => []]]);

        $emptyApp = $this->makeMiniAppMock('');
        $validToken = new FakeAccessToken();
        $validApp = $this->makeMiniAppMock('mini-app-1', $validToken, 'http://proxy');

        $this->app->instance('easywechat.mini_app.empty', $emptyApp);
        $this->app->instance('easywechat.mini_app.valid', $validApp);

        $job = new RefreshWeChatMiniAppAccessTokensJob(['proxy' => 'http://proxy']);

        $this->assertJob($job);

        $this->assertSame(1, $validToken->refreshCalls);
    }

    public function testOfficialAccountActionRefreshesTokens()
    {
        if (!class_exists(OfficialAccount::class)) {
            $this->markTestSkipped('OfficialAccount Application class is not available.');
        }

        config(['easywechat.official_account' => ['main' => []]]);

        $token = new FakeAccessToken();
        $app = $this->makeOfficialAccountMock('official-1', $token, 'http://proxy');

        $this->app->instance('easywechat.official_account.main', $app);

        $job = new RefreshWeChatOfficialAccountAccessTokensJob(['proxy' => 'http://proxy']);

        $this->assertJob($job);

        $this->assertSame(1, $token->refreshCalls);
    }

    private function makeMiniAppMock(string $appId, ?FakeAccessToken $token = null, ?string $proxy = null): MiniApp
    {
        $account = $this->makeMiniAppAccount($appId);

        $app = $this->getMockBuilder(MiniApp::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccount', 'getAccessToken', 'getHttpClient', 'setHttpClient'])
            ->getMock();

        $app->method('getAccount')->willReturn($account);

        if ($appId === '') {
            $app->expects($this->never())->method('getAccessToken');
            $app->expects($this->never())->method('getHttpClient');
            $app->expects($this->never())->method('setHttpClient');

            return $app;
        }

        $token = $token ?? new FakeAccessToken();
        $httpClient = $this->createMock(HttpClientInterface::class);

        if ($proxy !== null) {
            $httpClient->expects($this->once())
                ->method('withOptions')
                ->with(['proxy' => $proxy])
                ->willReturn($httpClient);
        }

        $app->expects($this->once())->method('getAccessToken')->willReturn($token);
        $app->expects($this->once())->method('getHttpClient')->willReturn($httpClient);
        $app->expects($this->once())->method('setHttpClient')->with($httpClient)->willReturn($app);

        return $app;
    }

    private function makeOfficialAccountMock(string $appId, FakeAccessToken $token, ?string $proxy = null): OfficialAccount
    {
        $account = $this->makeOfficialAccountAccount($appId);

        $app = $this->getMockBuilder(OfficialAccount::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccount', 'getAccessToken', 'getHttpClient', 'setHttpClient'])
            ->getMock();

        $httpClient = $this->createMock(HttpClientInterface::class);

        if ($proxy !== null) {
            $httpClient->expects($this->once())
                ->method('withOptions')
                ->with(['proxy' => $proxy])
                ->willReturn($httpClient);
        }

        $app->method('getAccount')->willReturn($account);
        $app->expects($this->once())->method('getAccessToken')->willReturn($token);
        $app->expects($this->once())->method('getHttpClient')->willReturn($httpClient);
        $app->expects($this->once())->method('setHttpClient')->with($httpClient)->willReturn($app);

        return $app;
    }

    private function makeMiniAppAccount(string $appId): MiniAppAccount
    {
        return new class($appId) implements MiniAppAccount {
            private string $appId;

            public function __construct(string $appId)
            {
                $this->appId = $appId;
            }

            public function getAppId(): string
            {
                return $this->appId;
            }

            public function getSecret(): string
            {
                return '';
            }

            public function getToken(): ?string
            {
                return null;
            }

            public function getAesKey(): ?string
            {
                return null;
            }
        };
    }

    private function makeOfficialAccountAccount(string $appId): OfficialAccountAccount
    {
        return new class($appId) implements OfficialAccountAccount {
            private string $appId;

            public function __construct(string $appId)
            {
                $this->appId = $appId;
            }

            public function getAppId(): string
            {
                return $this->appId;
            }

            public function getSecret(): string
            {
                return '';
            }

            public function getToken(): ?string
            {
                return null;
            }

            public function getAesKey(): ?string
            {
                return null;
            }
        };
    }
}

class FakeAccessToken implements AccessTokenInterface
{
    public int $refreshCalls = 0;

    public function refresh(): string
    {
        $this->refreshCalls++;

        return 'token-'.$this->refreshCalls;
    }

    public function getToken(): string
    {
        return 'token';
    }

    public function toQuery(): array
    {
        return [];
    }
}
