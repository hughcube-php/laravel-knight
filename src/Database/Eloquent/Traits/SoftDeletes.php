<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/11
 * Time: 19:36.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use HughCube\Laravel\Knight\Database\Eloquent\Builder;

/**
 * @method static Builder withTrashed(bool $withTrashed = true)
 * @method static Builder onlyTrashed()
 * @method static Builder withoutTrashed()
 */
trait SoftDeletes
{
    use \Illuminate\Database\Eloquent\SoftDeletes;
}
