<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/7/1
 * Time: 23:54.
 */

namespace HughCube\Laravel\Knight\Ide\Support;

use HughCube\Laravel\Knight\Mixin\Support\CollectionMixin;
use Illuminate\Support\Collection;

/**
 * @mixin Collection
 *
 * @deprecated 只是一个帮助类, 不要使用
 * @see CollectionMixin
 */
class KIdeCollection
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
     *
     * @see CollectionMixin::filterWithStop()
     */
    public function filterWithStop(callable $stop, $withStopItem = false): KIdeCollection
    {
        return $this;
    }

    /**
     * @return $this
     *
     * @see CollectionMixin::pluckAndMergeSetColumn()
     */
    public function pluckAndMergeSetColumn($name, $separator = ',', $filter = null): KIdeCollection
    {
        return $this;
    }

    /**
     * @return $this
     *
     * @see CollectionMixin::onlyArrayKeys()
     */
    public function onlyArrayKeys($keys = []): KIdeCollection
    {
        return $this;
    }

    /**
     * @return $this
     *
     * @see CollectionMixin::onlyColumnValues()
     */
    public function onlyColumnValues($values, $name = null, bool $strict = false): KIdeCollection
    {
        return $this;
    }

    /**
     * @return $this
     *
     * @see CollectionMixin::whenFilter()
     */
    public function whenFilter($when, callable $callable): KIdeCollection
    {
        return $this;
    }

    /**
     * @return $this
     *
     * @see CollectionMixin::mapInt()
     */
    public function mapInt(): KIdeCollection
    {
        return $this;
    }

    /**
     * @return $this
     *
     * @see CollectionMixin::mapString()
     */
    public function mapString(): KIdeCollection
    {
        return $this;
    }
}
