<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:07.
 */

namespace HughCube\Laravel\Knight\Mixin\Http;

use Closure;
use HughCube\Laravel\Knight\Http\Request as KnightRequest;
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
 *
 * @see KnightRequest
 * @deprecated
 */
class RequestMixin
{
    /**
     * 获取客户端版本.
     */
    public function getClientVersion(): Closure
    {
        return function (): ?string {
            /** @phpstan-ignore-next-line */
            return $this->headers->get(sprintf('%sVersion', $this->getClientHeaderPrefix()));
        };
    }

    /**
     * 获取客户端的随机字符串.
     */
    public function getClientNonce(): Closure
    {
        return function (): ?string {
            /** @phpstan-ignore-next-line */
            return $this->headers->get(sprintf('%sNonce', $this->getClientHeaderPrefix()));
        };
    }

    /**
     * 获取客户端的签名字符串.
     */
    public function getClientSignature(): Closure
    {
        return function (): ?string {
            /** @phpstan-ignore-next-line */
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

            /** @phpstan-ignore-next-line */
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
            /** @phpstan-ignore-next-line */
            return $this->headers->get('Date');
        };
    }

    public function getClientDate(): Closure
    {
        return function (): ?string {
            /** @phpstan-ignore-next-line */
            return $this->headers->get(sprintf('%sDate', $this->getClientHeaderPrefix()));
        };
    }

    /**
     * 获取agent检测.
     */
    public function getUserAgentDetect(): Closure
    {
        return function (): Agent {
            if (!property_exists($this, 'userAgentDetect') || !$this->userAgentDetect instanceof Agent) {
                /** @phpstan-ignore-next-line */
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
            /** @phpstan-ignore-next-line */
            return $this->isWeChat() && str_contains($this->userAgent(), 'miniProgram');
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
            /** @phpstan-ignore-next-line */
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
            /** @phpstan-ignore-next-line */
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
            /** @phpstan-ignore-next-line */
            return Version::compare($contain ? '<=' : '<', $this->getClientVersion(), $version, $length);
        };
    }

    /**
     * 获取最后一级目录.
     */
    public function getLastDirectory(): Closure
    {
        return function (): ?string {
            return Str::afterLast(trim($this->getPathInfo()), '/') ?: null;
        };
    }
}
