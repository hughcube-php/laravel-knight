<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/5/26
 * Time: 11:28.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent;

use HughCube\Laravel\Knight\Ide\Support\KIdeCollection;
use HughCube\Laravel\Knight\Mixin\Support\CollectionMixin;

/**
 * @see CollectionMixin
 *
 * @mixin KIdeCollection
 *
 * @template TKey of array-key
 * @template TModel of Model
 *
 * @extends \Illuminate\Support\Collection<TKey, TModel>
 *
 * @phpstan-ignore-next-line
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
}
