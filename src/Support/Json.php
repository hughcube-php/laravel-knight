<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:51.
 */

namespace HughCube\Laravel\Knight\Support;

class Json
{
    public static function decode($json, bool $associative = false, int $depth = 512, int $flags = 0)
    {
        $meta = json_decode($json, $associative, $depth, $flags);
        return JSON_ERROR_NONE === json_last_error() ? $meta : null;
    }

    public static function decodeArray($json, int $depth = 512, int $flags = 0): ?array
    {
        $meta = static::decode($json, true, $depth, $flags);
        return is_array($meta) ? $meta : null;
    }

    public static function decodeObject($json, int $depth = 512, int $flags = 0): ?object
    {
        $meta = static::decode($json, false, $depth, $flags);
        return is_object($meta) ? $meta : null;
    }
}
