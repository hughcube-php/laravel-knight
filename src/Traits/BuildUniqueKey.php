<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/4/3
 * Time: 22:21
 */

namespace HughCube\Laravel\Knight\Traits;

trait BuildUniqueKey
{
    protected function buildUniqueKey($data, $length = 32): string
    {
        $string = serialize($data);

        $crc32 = base_convert(abs(crc32($string)), 10, 36);
        $hash = sprintf('%s%s', $crc32, md5($string));

        return substr($hash, 0, $length);
    }
}
