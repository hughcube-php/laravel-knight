<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 17:33.
 */

namespace HughCube\Laravel\Knight\Routing;

/**
 * @mixin Action
 * @deprecated 已经废弃, 使用mixin在Request实现
 * @see RequestMixin
 */
trait ClientApiVersion
{
    abstract protected function getClientApiVersion(): string;

    protected function clientApiVersionFormat(string $version, ?int $length = null): string
    {
        $length = $length ?? 3;

        return implode('.', array_slice(array_pad((explode('.', $version) ?: []), $length, '0'), 0, $length));
    }

    protected function clientApiVersionCompare(string $operator, string $version, ?int $length = null): bool
    {
        return version_compare(
            $this->clientApiVersionFormat($this->getClientApiVersion(), $length),
            $this->clientApiVersionFormat($version, $length),
            $operator
        );
    }

    /**
     * 判断请求是否来自指定版本的客户端.
     *
     * 1.0(client) == 1.0 => true
     */
    protected function isEqClientApiVersion(string $version, ?int $length = null): bool
    {
        return $this->clientApiVersionCompare('=', $version, $length);
    }

    /**
     * 判断请求是否来自大于指定版本的客户端.
     *
     * 2.0(client) > 1.0 => true
     */
    protected function isLtClientApiVersion(string $version, bool $contain = false, ?int $length = null): bool
    {
        return $this->clientApiVersionCompare(($contain ? '>=' : '>'), $version, $length);
    }

    /**
     * 判断请求是否来自小于指定版本的客户端.
     *
     * 1.0(client) < 2.0 => true
     */
    protected function isGtClientApiVersion(string $version, bool $contain = false, ?int $length = null): bool
    {
        return $this->clientApiVersionCompare(($contain ? '<=' : '<'), $version, $length);
    }
}
