<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/18
 * Time: 10:31 下午.
 */

namespace HughCube\Laravel\Package;

use Illuminate\Support\Facades\Facade as IlluminateFacade;

/**
 * Class Package.
 *
 * @method static Store store(string $name = null)
 *
 * @see \HughCube\Laravel\Package\Manager
 * @see \HughCube\Laravel\Package\ServiceProvider
 */
class Facade extends IlluminateFacade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'package';
    }
}
