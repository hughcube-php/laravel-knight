<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/5/24
 * Time: 12:21.
 */

namespace HughCube\Laravel\Knight\Mixin\Support;

use Closure;
use Illuminate\Support\Str;

class StrMixin
{
    public function afterLast(): Closure
    {
        return function ($subject, $search) {
            if ($search === '') {
                return $subject;
            }

            $position = strrpos($subject, (string)$search);

            if ($position === false) {
                return $subject;
            }

            return substr($subject, $position + strlen($search));
        };
    }

    public function beforeLast(): Closure
    {
        return function ($subject, $search) {
            if ($search === '') {
                return $subject;
            }

            $pos = mb_strrpos($subject, $search);

            if ($pos === false) {
                return $subject;
            }

            return Str::substr($subject, 0, $pos);
        };
    }

    public function getMobilePattern(): Closure
    {
        return function () {
            return '/^(13[0-9]|14[0-9]|15[0-9]|16[0-9]|17[0-9]|18[0-9]|19[0-9])\d{8}$/';
        };
    }

    protected function checkMobile(): Closure
    {
        return function ($mobile, $iddCode = null): bool {
            if (!is_string($mobile) && !ctype_digit(strval($mobile))) {
                return false;
            }

            if (86 == $iddCode || null == $iddCode) {
                /** @phpstan-ignore-next-line */
                return false != preg_match(Str::getMobilePattern(), $mobile);
            }

            return true;
        };
    }
}
