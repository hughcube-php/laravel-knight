<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:07
 */

namespace HughCube\Laravel\Knight\Mixin\Http;

use Closure;
use HughCube\Laravel\Knight\Support\Version;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

/**
 * @mixin Request
 * @method null|string getClientVersion()
 * @property null|Agent $userAgentDetect
 */
class RequestMixin
{
    /**
     * 获取agent检测.
     */
    public function getUserAgentDetect(): Closure
    {
        return function (): Agent {
            if (!property_exists($this, 'userAgentDetect') || !$this->userAgentDetect instanceof Agent) {
                $this->userAgentDetect = new Agent($this->headers->all(), $this->userAgent());
            }
            return $this->userAgentDetect;
        };
    }

    /**
     * 判断是否在微信客户端内.
     */
    public function isWeChat(): Closure
    {
        return function (): bool {
            return str_contains($this->userAgent(), 'MicroMessenger');
        };
    }

    /**
     * 判断是否在微信客户端内.
     */
    public function isWeChatMiniProgram(): Closure
    {
        return function (): bool {
            return true == $this->isWeChat() && str_contains($this->userAgent(), 'miniProgram');
        };
    }

    /**
     * 判断请求是否来自指定版本的客户端.
     *
     * 1.0(client) == 1.0 => true
     */
    protected function isEqClientVersion(): Closure
    {
        return function (string $version, ?int $length = null): bool {
            return Version::compare('=', $this->getClientVersion(), $version, $length);
        };
    }

    /**
     * 判断请求是否来自大于指定版本的客户端.
     *
     * 2.0(client) > 1.0 => true
     */
    protected function isLtClientVersion(): Closure
    {
        return function (string $version, bool $contain = false, ?int $length = null): bool {
            return Version::compare(($contain ? '>=' : '>'), $this->getClientVersion(), $version, $length);
        };
    }

    /**
     * 判断请求是否来自小于指定版本的客户端.
     *
     * 1.0(client) < 2.0 => true
     */
    protected function isGtClientVersion(): Closure
    {
        return function (string $version, bool $contain = false, ?int $length = null): bool {
            return Version::compare(($contain ? '<=' : '<'), $this->getClientVersion(), $version, $length);
        };
    }
}
