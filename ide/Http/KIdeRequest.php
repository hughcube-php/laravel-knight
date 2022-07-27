<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/1
 * Time: 23:43.
 */

namespace HughCube\Laravel\Knight\Ide\Http;

use HughCube\Laravel\Knight\Mixin\Http\RequestMixin;
use Illuminate\Http\Request as IlluminateRequest;
use Jenssegers\Agent\Agent;
use Laravel\Lumen\Http\Request as LumenRequest;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * @mixin LumenRequest
 * @mixin IlluminateRequest
 *
 * @see RequestMixin
 * @deprecated 只是一个帮助类, 不要使用
 */
class KIdeRequest
{
    /**
     * @see RequestMixin::getClientHeaderPrefix()
     */
    public function getClientHeaderPrefix(): string
    {
        return '';
    }

    /**
     * @see RequestMixin::getClientVersion()
     */
    public function getClientVersion(): ?string
    {
        return null;
    }

    /**
     * 获取客户端的随机字符串.
     */
    public function getClientNonce(): ?string
    {
        return null;
    }

    /**
     * 获取客户端的所有请求头.
     */
    public function getClientHeaders(): HeaderBag
    {
        return new HeaderBag([]);
    }

    /**
     * 获取客户端日期
     */
    public function getDate(): ?string
    {
        return null;
    }

    /**
     * @see RequestMixin::getUserAgentDetect()
     */
    public function getUserAgentDetect(): Agent
    {
        return new Agent();
    }

    /**
     * @see RequestMixin::isWeChat()
     */
    public function isWeChat(): bool
    {
        return false;
    }

    /**
     * @see RequestMixin::isWeChat()
     */
    public function isWeChatMiniProgram(): bool
    {
        return false;
    }

    /**
     * @see RequestMixin::isEqClientVersion()
     */
    public function isEqClientVersion(string $version, ?int $length = null): bool
    {
        return false;
    }

    /**
     * @see RequestMixin::isLtClientVersion()
     */
    public function isLtClientVersion(string $version, bool $contain = false, ?int $length = null): bool
    {
        return false;
    }

    /**
     * @see RequestMixin::isGtClientVersion()
     */
    public function isGtClientVersion(string $version, bool $contain = false, ?int $length = null): bool
    {
        return false;
    }
}
