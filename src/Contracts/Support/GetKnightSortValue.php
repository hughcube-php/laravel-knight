<?php

namespace HughCube\Laravel\Knight\Contracts\Support;

/**
 * 获取对象的排序值
 *
 * 用于 Collection::sortKnightModel() 排序比较
 */
interface GetKnightSortValue
{
    /**
     * 获取排序值字符串
     *
     * @return string
     */
    public function getKnightSortValue(): string;
}
