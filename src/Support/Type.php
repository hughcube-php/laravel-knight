<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:40.
 */

namespace HughCube\Laravel\Knight\Support;

use Illuminate\Support\Collection;

class Type
{
    public static function int($value): ?int
    {
        return null === $value ? null : intval($value);
    }

    public static function float($value): ?float
    {
        return null === $value ? null : floatval($value);
    }

    public static function string($value): ?string
    {
        return null === $value ? null : strval($value);
    }

    public static function bool($value): ?bool
    {
        return null === $value ? null : boolval($value);
    }

    public static function collection($value): ?Collection
    {
        return null === $value ? null : Collection::make($value);
    }
}
