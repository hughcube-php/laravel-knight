<?php

namespace Illuminate\Support;

use HughCube\Laravel\Knight\Mixin\Support\StrMixin;

/**
 * IDE helper stub for Str mixins.
 *
 * @see StrMixin
 */
class Str
{
    /**
     * @see StrMixin::afterLast()
     */
    public static function afterLast($subject, $search): string
    {
        return '';
    }

    /**
     * @see StrMixin::beforeLast()
     */
    public static function beforeLast($subject, $search): string
    {
        return '';
    }

    /**
     * @see StrMixin::getMobilePattern()
     */
    public static function getMobilePattern(): string
    {
        return '';
    }

    /**
     * @see StrMixin::checkMobile()
     */
    public static function checkMobile($mobile, $iddCode = null): bool
    {
        return false;
    }

    /**
     * @see StrMixin::maskMobile()
     */
    public static function maskMobile($string): string
    {
        return '';
    }

    /**
     * @see StrMixin::maskChinaIdCode()
     */
    public static function maskChinaIdCode($string): string
    {
        return '';
    }

    /**
     * @see StrMixin::splitWhitespace()
     */
    public static function splitWhitespace($string, int $limit = -1, int $flags = 0): array
    {
        return [];
    }

    /**
     * @see StrMixin::isUtf8()
     */
    public static function isUtf8($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isOctal()
     */
    public static function isOctal($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isBinary()
     */
    public static function isBinary($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isHex()
     */
    public static function isHex($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isAlnum()
     */
    public static function isAlnum($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isAlpha()
     */
    public static function isAlpha($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isNaming()
     */
    public static function isNaming($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isWhitespace()
     */
    public static function isWhitespace($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isDigit()
     */
    public static function isDigit($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isEmail()
     */
    public static function isEmail($string, bool $isStrict = false): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isTel()
     */
    public static function isTel($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isIp()
     */
    public static function isIp($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isIp4()
     */
    public static function isIp4($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isIp6()
     */
    public static function isIp6($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isPrivateIp()
     */
    public static function isPrivateIp($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isPublicIp()
     */
    public static function isPublicIp($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isUrl()
     */
    public static function isUrl($string, bool $checkAccess = false): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isPort()
     */
    public static function isPort($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isTrue()
     */
    public static function isTrue($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isChineseName()
     */
    public static function isChineseName($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::hasChinese()
     */
    public static function hasChinese($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::isChinese()
     */
    public static function isChinese($string): bool
    {
        return false;
    }

    /**
     * @see StrMixin::convEncoding()
     */
    public static function convEncoding($contents, $from = 'gbk', $to = 'utf-8'): string
    {
        return '';
    }

    /**
     * @see StrMixin::msubstr()
     */
    public static function msubstr($str, $start = 0, $length = null, $suffix = '...', $charset = 'utf-8'): string
    {
        return '';
    }

    /**
     * @see StrMixin::countWords()
     */
    public static function countWords($string): int
    {
        return 0;
    }

    /**
     * @see StrMixin::offsetGet()
     */
    public static function offsetGet($string, $index): string
    {
        return '';
    }

    /**
     * @see StrMixin::filterPartialUTF8()
     */
    public static function filterPartialUTF8($string): string
    {
        return '';
    }

    /**
     * @see StrMixin::versionCompare()
     */
    public static function versionCompare(
        string $a,
        string $b,
        ?string $operator = null,
        ?int $compareDepth = null
    ): int
    {
        return 0;
    }

    /**
     * @see StrMixin::mbSplit()
     */
    public static function mbSplit(string $string, int $length = 1, ?string $encoding = null): array
    {
        return [];
    }
}
