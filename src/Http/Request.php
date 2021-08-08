<?php

namespace HughCube\Laravel\Knight\Http;

use Jenssegers\Agent\Agent;

trait Request
{
    /**
     * @var Agent
     */
    protected $userAgentDetect;

    /**
     * 获取agent检测.
     *
     * @return Agent
     */
    public function getUserAgentDetect(): Agent
    {
        if (!$this->userAgentDetect instanceof Agent) {
            $this->userAgentDetect = new Agent($this->headers->all(), $this->userAgent());
        }

        return $this->userAgentDetect;
    }

    /**
     * 判断是否在微信客户端内.
     *
     * @return bool
     */
    public function isWeChat(): bool
    {
        return false !== strpos($this->userAgent(), 'MicroMessenger');
    }

    /**
     * 判断是否在微信小程序客户端内.
     *
     * @return bool
     */
    public function isWeChatMiniProgram(): bool
    {
        return $this->isWeChat() && false !== strpos($this->userAgent(), 'miniProgram');
    }
}
