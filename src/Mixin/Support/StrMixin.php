<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/5/24
 * Time: 12:21
 */

namespace HughCube\Laravel\Knight\Mixin\Support;

use Closure;

class StrMixin
{
    public function afterLast(): Closure
    {
        return function ($subject, $search) {
            if ($search === '') {
                return $subject;
            }

            $position = strrpos($subject, (string) $search);

            if ($position === false) {
                return $subject;
            }

            return substr($subject, $position + strlen($search));
        };
    }
}
