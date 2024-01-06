<?php

namespace Carbon;

use Illuminate\Support\Carbon as IlluminateCarbon;

/**
 * @mixin IlluminateCarbon
 */
class Carbon
{
    public static function tryParse(): ?IlluminateCarbon
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
