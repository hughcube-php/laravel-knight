<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/6
 * Time: 8:29 下午.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent;

use HughCube\Laravel\Knight\Database\Eloquent\Traits\Builder as KnightBuilder;

/**
 * Class Builder.
 */
class Builder extends \Illuminate\Database\Eloquent\Builder
{
    use KnightBuilder;
}
