<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/5/26
 * Time: 11:28
 */

namespace HughCube\Laravel\Knight\Database\Eloquent;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection as IlluminateCollection;

class Collection extends \Illuminate\Database\Eloquent\Collection
{
    /**
     * @return static
     */
    public function filterAvailable()
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
}
