<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\WeChat\WeChat;

class RefreshWeChatMiniAppAccessTokensJob extends Job
{
    protected function action(): void
    {
        $accounts = $this->getContainerConfig()->get('easywechat.mini_app', []);
        foreach ($accounts as $name => $_) {
            $app = WeChat::miniApp($name);

            $appid = $app->getAccount()->getAppId();
            if (empty($appid)) {
                continue;
            }

            $token = $app->getAccessToken()->refresh();
            $this->info(sprintf('刷新小程序(%s)token: %s', $appid, $token));
        }
    }
}
