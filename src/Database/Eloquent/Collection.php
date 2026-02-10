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
 * @deprecated 使用 \Illuminate\Database\Eloquent\Collection 代替，filterAvailable() 和 sortKnightModel() 已迁移到 EloquentCollectionMixin
 *
 * @phpstan-ignore-next-line
 */
class Collection extends \Illuminate\Database\Eloquent\Collection
{
}
