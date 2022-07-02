<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/6
 * Time: 18:57.
 */

namespace HughCube\Laravel\Knight\Mixin\Support;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * @mixin Collection
 */
class CollectionMixin
{
    /**
     * 根据回调方法检查是否存在指定元素.
     */
    public function hasByCallable(): Closure
    {
        return function (callable $key) {
            foreach ($this->getIterator() as $index => $item) {
                if (true === $key($item, $index)) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * 是否是索引数组.
     */
    public function isIndexed(): Closure
    {
        return function (bool $consecutive = true) {
            if ($this->isEmpty()) {
                return true;
            }

            if ($consecutive) {
                return $this->keys()->toArray() === range(0, $this->count() - 1);
            }

            foreach ($this->all() as $index => $value) {
                if (!is_int($index)) {
                    return false;
                }
            }

            return true;
        };
    }

    /**
     * 过滤元素直到满足$stop(含stop的值).
     */
    public function filterWithStop(): Closure
    {
        return function (callable $stop) {
            $stopState = false;

            return $this->filter(function ($item) use (&$stopState, $stop) {
                return $stopState = $stopState || $stop($item);
            });
        };
    }

    /**
     * pluck指定set(1,2,3,4)元素, 并且合并后在分割为Collection.
     */
    public function pluckAndMergeSetColumn(): Closure
    {
        return function ($name, $separator = ',', $filter = null) {
            $string = $this->pluck($name)->implode($separator);
            if (empty($string)) {
                return $this->make()->toBase();
            }

            $items = Arr::wrap(explode($separator, $string));

            return $this->make($items)->toBase()->filter($filter)->unique()->sort()->values();
        };
    }

    /**
     * 收集指定数组keys, 组合成一个新的collection.
     */
    public function onlyArrayKeys(): Closure
    {
        return function ($keys = []) {
            $keys = $keys ?? [];

            $collection = $this->make();
            foreach ($this->getIterator() as $key => $item) {
                if (in_array($key, $keys)) {
                    $collection->put($key, $item);
                }
            }

            return $collection;
        };
    }

    /**
     * 收集指定属性的指定值, 组合成一个新的collection.
     */
    public function onlyColumnValues(): Closure
    {
        return function ($values, $name = null, bool $strict = false) {
            $collection = $this->make();
            foreach ($this->getIterator() as $key => $item) {

                /** 之所以每次计算$column, 因为可能存在不同model在同一个collection */
                if (null === $name && is_object($item) && method_exists($item, 'getKeyName')) {
                    $column = $item->getKeyName();
                } else {
                    $column = $name;
                }

                if (in_array($item[$column], $values, $strict)) {
                    $collection->put($key, $item);
                }
            }

            return $collection;
        };
    }

    /**
     * 满足条件在执行过滤.
     */
    public function whenFilter(): Closure
    {
        return function ($when, callable $callable) {
            if ($when) {
                return $this->filter($callable);
            }

            return $this->make($this->getIterator());
        };
    }
}
