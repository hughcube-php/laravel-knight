<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/3
 * Time: 15:03.
 */

namespace HughCube\Laravel\Knight\Mixin\Database\Eloquent;

use Closure;
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

/**
 * @mixin-target \Illuminate\Database\Eloquent\Collection
 */
class CollectionMixin
{
    /**
     * @return static
     */
    public function filterAvailable(): Closure
    {
        return function () {
            return $this->filter(function ($model) {
                /** @var Model $model */
                return $model->isAvailable();
            });
        };
    }

    /**
     * 按 GetKnightSortValue::getKSortValue() 降序排序.
     *
     * @return static
     */
    public function sortKnightModel(): Closure
    {
        return function () {
            return $this->sortByDesc(function ($item) {
                return $item->getKSortValue();
            });
        };
    }
}
