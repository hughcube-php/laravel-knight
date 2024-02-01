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
}
