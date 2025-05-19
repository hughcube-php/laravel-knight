<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:51.
 */

namespace HughCube\Laravel\Knight\Support;

class Arr extends \Illuminate\Support\Arr
{
    public static function getCombos(array $fields, int $minLength = 2): array
    {
        $items = [];

        $n = count($fields);
        $total = 1 << $n;
        for ($mask = 0; $mask < $total; $mask++) {
            $combo = [];

            for ($i = 0; $i < $n; $i++) {
                if ($mask & (1 << $i)) {
                    $combo[] = $fields[$i];
                }
            }

            if (count($combo) >= $minLength) {
                $items[] = $combo;
            }
        }

        return $items;
    }
}
