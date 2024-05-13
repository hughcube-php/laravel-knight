<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/6
 * Time: 18:57.
 */

namespace HughCube\Laravel\Knight\Mixin\Support;

use Carbon\Carbon;
use Closure;
use HughCube\Base\Base;
use HughCube\CNNumber\CNNumber;
use Throwable;

/**
 * @mixin Carbon
 */
class CarbonMixin
{
    public static function tryParse(): Closure
    {
        return function ($date = null, $tz = null): ?Carbon {
            if (empty($date)) {
                return null;
            }

            try {
                $date = static::parse($date, $tz);
            } catch (Throwable $exception) {
                $date = false;
            }

            return $date instanceof Carbon ? $date : null;
        };
    }

    public function getTimestampAsFloat(): Closure
    {
        return function (): float {
            /** @phpstan-ignore-next-line */
            return $this->getPreciseTimestamp() / static::MICROSECONDS_PER_SECOND;
        };
    }

    /**
     * Mainly used for BC Math extensions.
     */
    public function getTimestampAsString(): Closure
    {
        return function (): string {
            return sprintf(
                '%s.%s',
                $this->getTimestamp(),
                /** @phpstan-ignore-next-line */
                ($this->getPreciseTimestamp() % static::MICROSECONDS_PER_SECOND) ?: '0'
            );
        };
    }

    public function toRfc3339ExtendedString(): Closure
    {
        return function (): string {
            return $this->toRfc3339String(true);
        };
    }

    public function toChineseDate(): Closure
    {
        return function (): string {
            return sprintf(
                '%s年%s月%s日',
                strtr(
                    /** @phpstan-ignore-next-line */
                    Base::toString($this->year),
                    [
                        '0' => '〇', '1' => '一', '2' => '二', '3' => '三', '4' => '四',
                        '5' => '五', '6' => '六', '7' => '七', '8' => '八', '9' => '九',
                    ]
                ),
                /** @phpstan-ignore-next-line */
                CNNumber::toLower($this->month),
                /** @phpstan-ignore-next-line */
                CNNumber::toLower($this->day)
            );
        };
    }

    public static function try(): Closure
    {
        return function (callable $callable, $default = null) {
            try {
                return $callable();
            } catch (Throwable $exception) {
            }

            return $default;
        };
    }
}
