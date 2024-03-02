<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use EasyWeChat\OfficialAccount\Application as OfficialAccount;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\WeChat\WeChat;

class RefreshWeChatOfficialAccountAccessTokensJob extends Job
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
        $accounts = $this->getContainerConfig('easywechat.official_account', []);
        foreach ($accounts as $name => $_) {
            $app = WeChat::officialAccount($name);

            $appid = $app->getAccount()->getAppId();
            if (empty($appid)) {
                continue;
            }

            $app = $this->resetHttpClient($app);

            $token = $app->getAccessToken()->refresh();

            $this->info(sprintf('刷新公众号(%s)token: %s', $appid, $token));
        }
    }

    protected function resetHttpClient(OfficialAccount $app): OfficialAccount
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
