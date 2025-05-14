<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:40.
 */

namespace HughCube\Laravel\Knight\Support;

class Type
{
    public static function int($value): ?int
    {
        return null === $value ? null : (int) $value;
    }

    public static function string($value): ?int
    {
        return null === $value ? null : (string) $value;
    }
}
