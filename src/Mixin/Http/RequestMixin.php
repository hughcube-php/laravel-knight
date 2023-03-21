<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:07.
 */

namespace HughCube\Laravel\Knight\Mixin\Http;

use Closure;
use HughCube\Laravel\Knight\Support\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @mixin Request
 *
 * @method null|string getClientHeaderPrefix()
 *
 * @property null|Agent $userAgentDetect
 */
class RequestMixin
{
    /**
     * 获取客户端Header信息.
     */
    public function getIHKCClientHeader(): Closure
    {
        return function ($name): ?string {
            return $this->headers->get(
                sprintf('%s%s', $this->getClientHeaderPrefix(), $name)
            );
        };
    }

    /**
     * 获取客户端版本.
     */
    public function getClientVersion(): Closure
    {
        return function (): ?string {
            return $this->headers->get(sprintf('%sVersion', $this->getClientHeaderPrefix()));
        };
    }

    /**
     * 获取客户端的随机字符串.
     */
    public function getClientNonce(): Closure
    {
        return function (): ?string {
            return $this->headers->get(sprintf('%sNonce', $this->getClientHeaderPrefix()));
        };
    }

    /**
     * 获取客户端的签名字符串.
     */
    public function getClientSignature(): Closure
    {
        return function (): ?string {
            return $this->headers->get(sprintf('%sSignature', $this->getClientHeaderPrefix()));
        };
    }

    /**
     * 获取客户端的所有请求头.
     */
    public function getClientHeaders(): Closure
    {
        return function (): HeaderBag {
            $headers = [];
            foreach ($this->headers as $name => $values) {
                if (Str::startsWith(strtolower($name), strtolower($this->getClientHeaderPrefix()))) {
                    $headers[$name] = $values;
                }
            }

            return new HeaderBag($headers);
        };
    }

    /**
     * 获取客户端日期
     */
    public function getDate(): Closure
    {
        return function (): ?string {
            return $this->headers->get('Date');
        };
    }

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
     * 判断是否在postmen.
     */
    public function isPostmen(): Closure
    {
        return function (): bool {
            return Str::startsWith($this->userAgent(), 'PostmanRuntime');
        };
    }

    /**
     * 判断请求是否来自指定版本的客户端.
     *
     * 1.0(client) == 1.0 => true
     */
    public function isEqClientVersion(): Closure
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
    public function isLtClientVersion(): Closure
    {
        return function (string $version, bool $contain = false, ?int $length = null): bool {
            return Version::compare($contain ? '>=' : '>', $this->getClientVersion(), $version, $length);
        };
    }

    /**
     * 判断请求是否来自小于指定版本的客户端.
     *
     * 1.0(client) < 2.0 => true
     */
    public function isGtClientVersion(): Closure
    {
        return function (string $version, bool $contain = false, ?int $length = null): bool {
            return Version::compare($contain ? '<=' : '<', $this->getClientVersion(), $version, $length);
        };
    }
}
