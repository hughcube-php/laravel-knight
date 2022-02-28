<?php

namespace HughCube\Laravel\Knight\Http;

use Jenssegers\Agent\Agent;

trait Request
{
    /**
     * @var null|Agent
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
        return str_contains($this->userAgent(), 'MicroMessenger');
    }

    /**
     * 判断是否在微信小程序客户端内.
     *
     * @return bool
     */
    public function isWeChatMiniProgram(): bool
    {
        return $this->isWeChat() && str_contains($this->userAgent(), 'miniProgram');
    }
}
