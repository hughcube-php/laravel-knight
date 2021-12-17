<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:51
 */

namespace HughCube\Laravel\Knight\Support;

use DateTime;

class Carbon extends \Illuminate\Support\Carbon
{
    public static function fromDate($date, string $format = 'Y-m-d H:i:s'): null|static
    {
        if (empty($date)) {
            return null;
        }

        $results = static::createFromFormat($format, $date);
        return $results instanceof static ? $results : null;
    }

    public static function asDate($value, $format = 'Y-m-d H:i:s'): ?string
    {
        if ($value instanceof DateTime) {
            return $value->format($format);
        }

        if (empty($value)) {
            return null;
        }

        if (is_numeric($value) && $value > 0) {
            return date($format, $value);
        }

        return $value;
    }

    public static function isPastDate($date, string $format = 'Y-m-d H:i:s'): bool
    {
        $date = static::fromDate($date, $format);
        return $date instanceof static && $date->isPast();
    }

    public static function isPastTimestamp($timestamp): bool
    {
        return !empty($timestamp) && $timestamp <= time();
    }
}
