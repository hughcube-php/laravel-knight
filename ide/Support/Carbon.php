<?php

namespace Carbon;

use HughCube\Laravel\Knight\Mixin\Support\CarbonMixin;
use Illuminate\Support\Carbon as IlluminateCarbon;

/**
 * @mixin IlluminateCarbon
 *
 * @see CarbonMixin
 */
class Carbon
{
    /**
     * @see CarbonMixin::tryParse()
     */
    public static function tryParse($date = null, $tz = null): ?IlluminateCarbon
    {
        return null;
    }

    public function getTimestampAsFloat(): float
    {
        return 0;
    }

    public function getTimestampAsString(): string
    {
        return '';
    }

    public function toRfc3339ExtendedString(): string
    {
        return '';
    }

    public function toChineseDate(): string
    {
        return '';
    }

    /**
     * @see CarbonMixin::try()
     *
     * @return mixed
     */
    public static function try(callable $callable, $default = null)
    {
        return null;
    }

    /**
     * @see CarbonMixin::tryCreateFromFormat()
     *
     * @return null|static
     */
    public static function tryCreateFromFormat($format, $time, $timezone = null)
    {
        return null;
    }

    /**
     * @see CarbonMixin::tryParseDate()
     *
     * @return null|static
     */
    public static function tryParseDate($date)
    {
        return null;
    }

    /**
     * @see CarbonMixin::tryCreateFromFormats()
     *
     * @return null|static
     */
    public static function tryCreateFromFormats($date, $formats)
    {
        return null;
    }
}
