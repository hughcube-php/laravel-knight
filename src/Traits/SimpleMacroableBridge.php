<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 14:51.
 */

namespace HughCube\Laravel\Knight\Traits;

use Illuminate\Support\Traits\Macroable;
use ReflectionException;

/**
 * @see Macroable
 */
trait SimpleMacroableBridge
{
    /**
     * @param  class-string<Macroable>  $target
     * @param  bool  $replace
     * @return void
     * @throws ReflectionException
     */
    public static function mixin(string $target, bool $replace = false)
    {
        /** @phpstan-ignore-next-line */
        $mixin = new static;

        foreach (get_class_methods($mixin) as $method) {
            if ($method == __FUNCTION__) {
                break;
            }

            if ($replace || !$target::hasMacro($method)) {
                $target::macro($method, $mixin->{$method}());
            }
        }
    }
}
