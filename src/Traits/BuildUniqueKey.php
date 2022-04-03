<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/4/3
 * Time: 22:21.
 */

namespace HughCube\Laravel\Knight\Traits;

trait BuildUniqueKey
{
    /**
     * @param mixed    $data
     * @param int|null $length
     *
     * @return string
     */
    protected function buildUniqueKey($data, ?int $length = null): string
    {
        $string = serialize($data);

        $crc32 = base_convert(abs(crc32($string)), 10, 36);
        $hash = sprintf('%s%s', $crc32, md5($string));

        return null === $length ? $hash : substr($hash, 0, $length);
    }
}
