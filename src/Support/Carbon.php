<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:51.
 */

namespace HughCube\Laravel\Knight\Support;

use DateTime;
use DateTimeZone;

/**
 * @method static static|false createFromFormat(string $format, string $time, string|DateTimeZone $timezone = null)
 */
class Carbon extends \Illuminate\Support\Carbon
{
    /**
     * @return float
     */
    public function getTimestampAsFloat(): float
    {
        return $this->getPreciseTimestamp() / static::MICROSECONDS_PER_SECOND;
    }

    /**
     * @param string|null $date
     * @param string      $format
     *
     * @return static|false|null
     */
    public static function fromDate(?string $date, string $format = 'Y-m-d H:i:s')
    {
        if (empty($date)) {
            return null;
        }

        return static::createFromFormat($format, $date);
    }

    /**
     * @param DateTime|int|float $value
     * @param string             $format
     *
     * @return string|null
     */
    public static function asDate($value, string $format = 'Y-m-d H:i:s'): ?string
    {
        if ($value instanceof DateTime) {
            return $value->format($format);
        }

        if (is_numeric($value) && $value > 0) {
            return static::createFromTimestamp($value)->format($format);
        }

        return null;
    }

    /**
     * @param mixed  $date
     * @param string $format
     *
     * @return bool
     */
    public static function isPastDate($date, string $format = 'Y-m-d H:i:s'): bool
    {
        $dateTime = static::fromDate($date, $format);

        return $dateTime instanceof static && $dateTime->isPast();
    }

    /**
     * @param mixed $timestamp
     *
     * @return bool
     */
    public static function isPastTimestamp($timestamp): bool
    {
        return is_numeric($timestamp) && $timestamp <= time();
    }

    /**
     * @param string $date
     * @param bool   $extended
     *
     * @return static|false
     */
    public static function createFromRfc3339(string $date, bool $extended = false)
    {
        $format = $extended ? static::RFC3339_EXTENDED : static::RFC3339;

        return static::createFromFormat($format, $date);
    }

    /**
     * @param string $date
     *
     * @return static|false
     */
    public static function createFromRfc3339Extended(string $date)
    {
        return static::createFromRfc3339($date, true);
    }

    /**
     * @return string
     */
    public function toRfc3339ExtendedString(): string
    {
        return $this->toRfc3339String(true);
    }
}
