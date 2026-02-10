<?php

namespace HughCube\Laravel\Knight\Traits;

use HughCube\Base\Base;

/**
 * 排序值计算 trait
 *
 * 将 sort 和 id 各填充到 40 个字符长度，拼接为排序值字符串
 * 需要对象具有 sort 和 id 属性
 *
 * @see \HughCube\Laravel\Knight\Contracts\Support\GetKnightSortValue
 *
 * @property int $sort
 * @property int $id
 */
trait GetKnightSortValueTrait
{
    /**
     * 获取排序值字符串
     *
     * @return string
     */
    public function getKSortValue(): string
    {
        $sort = Base::toStringWithPad($this->sort ?? 0, 40);
        $id = Base::toStringWithPad($this->id ?? 0, 40);

        return $sort . $id;
    }
}
