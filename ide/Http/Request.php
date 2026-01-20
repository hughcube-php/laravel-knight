<?php

namespace Illuminate\Http;

use HughCube\Laravel\Knight\Http\Request as KnightRequest;
use HughCube\Laravel\Knight\Mixin\Http\RequestMixin;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * IDE helper stub for Request mixins.
 *
 * @see RequestMixin
 */
class Request
{
    /**
     * @see KnightRequest::getClientHeaderPrefix()
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
     * @see RequestMixin::getClientNonce()
     */
    public function getClientNonce(): ?string
    {
        return null;
    }

    /**
     * @see RequestMixin::getClientSignature()
     */
    public function getClientSignature(): ?string
    {
        return null;
    }

    /**
     * @see RequestMixin::getClientHeaders()
     */
    public function getClientHeaders(): HeaderBag
    {
        return new HeaderBag([]);
    }

    /**
     * @see RequestMixin::getDate()
     */
    public function getDate(): ?string
    {
        return null;
    }

    /**
     * @see RequestMixin::getClientDate()
     */
    public function getClientDate(): ?string
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
     * @see RequestMixin::isWeChatMiniProgram()
     */
    public function isWeChatMiniProgram(): bool
    {
        return false;
    }

    /**
     * @see RequestMixin::isPostmen()
     */
    public function isPostmen(): bool
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

    /**
     * @see RequestMixin::getLastDirectory()
     */
    public function getLastDirectory(): ?string
    {
        return null;
    }
}
