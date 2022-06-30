<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:40
 */

namespace HughCube\Laravel\Knight\Support;

use Illuminate\Support\Collection;

class Version
{
    /**
     * 填充版本字符串
     */
    public static function pad(string $version, ?int $length = null): string
    {
        $length = $length ?: 3;

        return Collection::make(explode('.', $version) ?: [])
            ->pad($length, '0')
            ->splice(0, $length)
            ->implode('.');
    }

    /**
     * 比较两个版本号
     */
    public static function compare(string $operator, string $a, string $b, ?int $length = null): bool
    {
        return version_compare(static::pad($a, $length), static::pad($b, $length), $operator);
    }
}
