<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/3
 * Time: 15:12.
 */

namespace HughCube\Laravel\Knight\Ide\Database\Query;

use HughCube\Laravel\Knight\Database\Eloquent\Traits\Builder as KnightBuilder;
use HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin;
use Illuminate\Database\Eloquent\Builder;

/**
 * @deprecated 只是一个帮助类, 不要使用
 */
class KIdeBuilder
{
    /**
     * @return null|Builder|KnightBuilder
     *
     * @see BuilderMixin::whereJsonOverlaps()
     */
    public function whereJsonOverlaps($column, $value, $boolean = 'and', $not = false)
    {
        return null;
    }

    /**
     * @return null|Builder|KnightBuilder
     *
     * @see BuilderMixin::orWhereJsonOverlaps()
     */
    public function orWhereJsonOverlaps($column, $value)
    {
        return null;
    }
}
