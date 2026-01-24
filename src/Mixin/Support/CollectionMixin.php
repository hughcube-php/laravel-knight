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
 * @mixin-target Collection
 */
class CollectionMixin
{
    /**
     * 根据回调方法检查是否存在指定元素.
     *
     * @deprecated
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

    public function hasAnyValues(): Closure
    {
        return function ($values, bool $strict = false) {
            if ($this->isEmpty()) {
                return false;
            }

            if (0 === count($values)) {
                return true;
            }

            foreach ($this->getIterator() as $item) {
                if (in_array($item, $values, $strict)) {
                    return true;
                }
            }

            return false;
        };
    }

    public function hasAllValues(): Closure
    {
        return function ($values, bool $strict = false) {
            if ($this->isEmpty()) {
                return false;
            }

            if (0 === count($values)) {
                return true;
            }

            foreach ($this->getIterator() as $item) {
                if (!in_array($item, $values, $strict)) {
                    return false;
                }
            }

            return true;
        };
    }

    public function hasValue(): Closure
    {
        return function ($needle, $strict = false) {
            foreach ($this->getIterator() as $index => $item) {
                if ($needle instanceof Closure && $needle($item, $index)) {
                    return true;
                } elseif ($strict && $item === $needle) {
                    return true;
                } elseif (!$strict && $item == $needle) {
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

//    public function afterKeyItems(): Closure
//    {
//        return function ($key = null) {
//            $collection = $this->make();
//
//            return $this->filter(function ($item) use (&$preSearched, $value, $withBeacon, $strict) {
//                if ($preSearched) {
//                    return true;
//                }
//
//                $searched = false;
//                if ($value instanceof Closure && $value($item)) {
//                    $searched = true;
//                } elseif ($strict && $item === $value) {
//                    $searched = true;
//                } elseif (!$strict && $item == $value) {
//                    $searched = true;
//                }
//
//                if ($searched) {
//                    $preSearched = $searched;
//                }
//
//                return $withBeacon && $searched;
//            });
//        };
//    }

    /**
     * 返回指定元素之后的所有元素.
     */
    public function afterFirstItems(): Closure
    {
        return function ($value = null, $withBeacon = false, $strict = false) {
            return $this->filter(function ($item) use (&$preSearched, $value, $withBeacon, $strict) {
                if ($preSearched) {
                    return true;
                }

                $searched = false;
                if ($value instanceof Closure && $value($item)) {
                    $searched = true;
                } elseif ($strict && $item === $value) {
                    $searched = true;
                } elseif (!$strict && $item == $value) {
                    $searched = true;
                }

                if ($searched) {
                    $preSearched = $searched;
                }

                return $withBeacon && $searched;
            });
        };
    }

    /**
     * 返回指定元素之后的所有元素.
     */
    public function afterLastItems(): Closure
    {
        return function ($value = null, $withBeacon = false, $strict = false) {
            $preSearched = false;

            return $this->filter(function ($item) use (&$preSearched, $value, $withBeacon, $strict) {
                if ($preSearched) {
                    return true;
                }

                $searched = false;
                if ($value instanceof Closure && $value($item)) {
                    $searched = true;
                } elseif ($strict && $item === $value) {
                    $searched = true;
                } elseif (!$strict && $item == $value) {
                    $searched = true;
                }

                if ($searched) {
                    $preSearched = $searched;
                }

                return $withBeacon && $searched;
            });
        };
    }

    /**
     * 过滤元素直到满足$stop.
     */
    public function filterWithStop(): Closure
    {
        return function (callable $stop, $withStopItem = false) {
            $stopState = false;

            return $this->filter(function ($item) use (&$stopState, $stop, $withStopItem) {
                $preStopState = $stopState;
                $stopState = $stopState || $stop($item);

                return $preStopState || ($withStopItem && $stopState);
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
     * 合并指定列中的数组元素.
     */
    public function pluckAndMergeArrayColumn(): Closure
    {
        return function ($name) {
            $merged = $this->make([]);

            foreach ($this->pluck($name) as $items) {
                if ($items instanceof Collection) {
                    $merged = $merged->merge($items);
                }elseif (is_array($items)){
                    $merged = $merged->merge($items);
                }else{
                    $merged = $merged->merge($this->make($items));
                }
            }

            return $merged;
        };
    }

    /**
     * 收集指定数组keys, 组合成一个新的collection.
     */
    public function onlyArrayKeys(): Closure
    {
        return function ($keys = []) {
            $keys = $this->wrap($keys);
            $collection = $this->make();

            foreach ($this->getIterator() as $key => $item) {
                /** @phpstan-ignore-next-line */
                if ($keys->hasValue($key, true)) {
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
            $values = $this->wrap($values);

            foreach ($this->getIterator() as $key => $item) {
                /** 之所以每次计算$column, 因为可能存在不同model在同一个collection */
                if (null === $name && is_object($item) && method_exists($item, 'getKeyName')) {
                    $column = $item->getKeyName();
                } else {
                    $column = $name;
                }

                /** @phpstan-ignore-next-line */
                if ($values->hasValue($item[$column], $strict)) {
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

    /**
     * map int.
     */
    public function mapInt(): Closure
    {
        return function () {
            return $this->map(function ($item) {
                return intval($item);
            });
        };
    }

    /**
     * map string.
     */
    public function mapString(): Closure
    {
        return function () {
            return $this->map(function ($item) {
                return strval($item);
            });
        };
    }

    /**
     * explode.
     */
    public function explode(): Closure
    {
        return function (string $separator, string $string, int $limit = PHP_INT_MAX) {
            return static::make(explode($separator, $string, $limit) ?: []);
        };
    }

    /**
     * split.
     * /[,，]/
     * /\s+/.
     */
    public function splitWhitespace(): Closure
    {
        return function (string $separator, string $pattern = '/\s+/', int $limit = -1) {
            return static::make(preg_split($pattern, $separator, $limit) ?: []);
        };
    }

    /**
     * split ,.
     */
    public function splitComma(): Closure
    {
        return function (string $separator, string $pattern = '/[,，]/', int $limit = -1) {
            return static::make(preg_split($pattern, $separator, $limit) ?: []);
        };
    }

    /**
     * split \/／.
     */
    public function splitSlash(): Closure
    {
        return function (string $separator, string $pattern = '#[\/／]#', int $limit = -1) {
            return static::make(preg_split($pattern, $separator, $limit) ?: []);
        };
    }
}
