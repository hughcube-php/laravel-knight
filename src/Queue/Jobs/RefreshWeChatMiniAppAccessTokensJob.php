<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use EasyWeChat\MiniApp\Application as MiniApp;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\WeChat\WeChat;

class RefreshWeChatMiniAppAccessTokensJob extends Job
{
    /**
     * @return array
     */
    protected function rules(): array
    {
        return [
            'proxy' => ['nullable', 'string'],
        ];
    }

    protected function action(): void
    {
        $accounts = $this->getContainerConfig()->get('easywechat.mini_app', []);
        foreach ($accounts as $name => $_) {
            $app = WeChat::miniApp($name);

            $appid = $app->getAccount()->getAppId();
            if (empty($appid)) {
                continue;
            }

            $app = $this->resetHttpClient($app);

            $token = $app->getAccessToken()->refresh();

            $this->info(sprintf('刷新小程序(%s)token: %s', $appid, $token));
        }
    }

    protected function resetHttpClient(MiniApp $app): MiniApp
    {
        $proxy = $this->p('proxy');
        if (empty($proxy)) {
            return $app;
        }

        $httpClient = $app->getHttpClient()->withOptions([
            'proxy' => $this->p('proxy'),
        ]);

        return $app->setHttpClient($httpClient);
    }
}
