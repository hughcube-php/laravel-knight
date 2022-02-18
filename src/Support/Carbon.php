<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:51.
 */

namespace HughCube\Laravel\Knight\Support;

use Carbon\Carbon as BaseCarbon;
use DateTime;

class Carbon extends \Illuminate\Support\Carbon
{
    public static function fromDate($date, string $format = 'Y-m-d H:i:s'): null|static
    {
        if (empty($date)) {
            return null;
        }

        $dateTime = static::createFromFormat($format, $date);
        return $dateTime instanceof static ? $dateTime : null;
    }

    public static function asDate($value, $format = 'Y-m-d H:i:s'): ?string
    {
        if ($value instanceof DateTime) {
            return $value->format($format);
        }

        if (is_numeric($value) && $value > 0) {
            return date($format, $value);
        }

        return null;
    }

    public static function isPastDate($date, string $format = 'Y-m-d H:i:s'): bool
    {
        $dateTime = static::fromDate($date, $format);
        return $dateTime instanceof BaseCarbon && $dateTime->isPast();
    }

    public static function isPastTimestamp($timestamp): bool
    {
        return !empty($timestamp) && $timestamp <= time();
    }
}
