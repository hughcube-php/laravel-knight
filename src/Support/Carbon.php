<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:51.
 */

namespace HughCube\Laravel\Knight\Support;

use DateTimeInterface;
use DateTimeZone;
use HughCube\Base\Base;
use HughCube\CNNumber\CNNumber;
use InvalidArgumentException;
use Throwable;

/**
 * @method static static|false createFromFormat(string $format, string $time, string|DateTimeZone $timezone = null)
 *
 * @deprecated
 */
class Carbon extends \Illuminate\Support\Carbon
{
    public function getTimestampAsFloat(): float
    {
        return $this->getPreciseTimestamp() / static::MICROSECONDS_PER_SECOND;
    }

    /**
     * Mainly used for BC Math extensions.
     */
    public function getTimestampAsString(): string
    {
        return sprintf(
            '%s.%s',
            $this->getTimestamp(),
            ($this->getPreciseTimestamp() % static::MICROSECONDS_PER_SECOND) ?: '0'
        );
    }

    /**
     * @param DateTimeInterface|int|float|string $date
     * @param string|null                        $format
     *
     * @return static|null
     */
    public static function fromDate($date, ?string $format = null): ?Carbon
    {
        if (empty($date)) {
            return null;
        }

        if ($date instanceof DateTimeInterface) {
            return static::instance($date);
        }

        if (is_numeric($date) && empty($format)) {
            return static::createFromTimestamp($date);
        }

        try {
            $dateTime = static::createFromFormat($format, $date);
            /** @phpstan-ignore-next-line */
        } catch (InvalidArgumentException $e) {
            $dateTime = false;
        }

        if ($dateTime instanceof static) {
            return $dateTime;
        }

        return static::parse($date);
    }

    /**
     * @param DateTimeInterface|int|float $value
     * @param string                      $format
     *
     * @return string|null
     */
    public static function asDate($value, string $format = 'Y-m-d H:i:s'): ?string
    {
        $dateTime = static::fromDate($value);

        return $dateTime instanceof static ? $dateTime->format($format) : null;
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

    /**
     * @param string|DateTimeInterface|null $time
     * @param DateTimeZone|string|null      $tz
     *
     * @return Carbon|null
     */
    public static function tryParse($time = null, $tz = null): ?Carbon
    {
        if (empty($time)) {
            return null;
        }

        try {
            $date = static::parse($time, $tz);
        } catch (Throwable $exception) {
            $date = false;
        }

        return $date instanceof static ? $date : null;
    }

    public function toChineseDate(): string
    {
        return sprintf(
            '%s年%s月%s日',
            strtr(
                Base::toString($this->year),
                [
                    '0' => '〇', '1' => '一', '2' => '二', '3' => '三', '4' => '四',
                    '5' => '五', '6' => '六', '7' => '七', '8' => '八', '9' => '九',
                ]
            ),
            CNNumber::toLower($this->month),
            CNNumber::toLower($this->day)
        );
    }
}
