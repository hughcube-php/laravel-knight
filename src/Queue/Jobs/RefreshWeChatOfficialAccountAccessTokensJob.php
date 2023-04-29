<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\WeChat\WeChat;

class RefreshWeChatOfficialAccountAccessTokensJob extends Job
{
    protected function action(): void
    {
        $accounts = $this->getContainerConfig()->get('easywechat.official_account', []);
        foreach ($accounts as $name => $_) {
            $app = WeChat::officialAccount($name);

            $appid = $app->getAccount()->getAppId();
            if (empty($appid)) {
                continue;
            }

            $token = $app->getAccessToken()->refresh();

            $this->info(sprintf('刷新公众号(%s)token: %s', $appid, $token));
        }
    }
}
