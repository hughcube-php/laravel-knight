<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/1
 * Time: 23:54
 */

namespace HughCube\Laravel\Knight\Ide\Support;

use HughCube\Laravel\Knight\Mixin\Support\CollectionMixin;

/**
 * @mixin \Illuminate\Support\Collection
 * @deprecated 只是一个帮助类, 不要使用
 * @see CollectionMixin
 */
class Collection
{
    /**
     * @see CollectionMixin::hasByCallable()
     */
    public function hasByCallable(callable $key): bool
    {
        return false;
    }

    /**
     * @see CollectionMixin::isIndexed()
     */
    public function isIndexed(bool $consecutive = true): bool
    {
        return false;
    }

    /**
     * @return $this
     * @see CollectionMixin::filterWithStop()
     */
    protected function filterWithStop(callable $stop): Collection
    {
        return $this;
    }
}
