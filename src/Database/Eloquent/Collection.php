<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/5/26
 * Time: 11:28.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent;

use HughCube\Laravel\Knight\Mixin\Support\CollectionMixin;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection as IlluminateCollection;

/**
 * @method bool       hasByCallable(callable $key)
 * @method bool       isIndexed(bool $consecutive = true)
 * @method Collection filterWithStop(callable $stop)
 *
 * @see CollectionMixin
 */
class Collection extends \Illuminate\Database\Eloquent\Collection
{
    /**
     * @return static
     */
    public function filterAvailable(): Collection
    {
        return $this->filter(function ($model) {
            /** @var Model $model */
            return $model->isAvailable();
        });
    }

    public function pluckAndMergeSetColumn($name, $separator = ',', $filter = null): IlluminateCollection
    {
        $string = $this->pluck($name)->implode($separator);

        if (empty($string)) {
            return IlluminateCollection::make();
        }

        return IlluminateCollection::make(Arr::wrap(explode($separator, $string)))->filter($filter)->unique()->values();
    }

    /**
     * @return static
     */
    public function onlyArrayKeys($keys): Collection
    {
        if (is_null($keys)) {
            return static::make($this->items);
        }

        $dictionary = Arr::only($this->getDictionary(), $keys);

        return static::make(array_values($dictionary));
    }

    /**
     * @return static
     */
    public function onlyColumnValues($values, $name = null): Collection
    {
        $dictionary = [];
        foreach ($this->items as $item) {
            $name = $name ?? $item->getKeyName();
            $dictionary[$item->{$name}][] = $item;
        }

        $items = [];
        foreach ($values as $value) {
            foreach (($dictionary[$value] ?? []) as $item) {
                $items[] = $item;
            }
        }

        return static::make($items);
    }
}
