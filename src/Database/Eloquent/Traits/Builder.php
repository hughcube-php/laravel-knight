<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/6
 * Time: 10:12 下午.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Carbon\CarbonPeriod;
use HughCube\Laravel\Knight\Database\Eloquent\Collection as KnightCollection;
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Ide\Database\Query\KIdeBuilder;
use HughCube\Laravel\Knight\Support\ParameterBag;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * Trait Builder.
 *
 * @method Model            getModel()
 * @method Connection       getConnection()
 * @method KnightCollection get()
 *
 * @mixin \Illuminate\Database\Eloquent\Builder
 * @mixin KIdeBuilder
 */
trait Builder
{
    /**
     * @var bool
     */
    protected bool $enableCache = true;

    /**
     * @return $this
     */
    public function noCache()
    {
        $this->enableCache = false;

        return $this;
    }

    /**
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        $cache = $this->enableCache ? $this->getModel()->getCache() : null;

        return $cache ?? $this->getNullCache();
    }

    /**
     * @return CacheInterface
     */
    protected function getNullCache(): CacheInterface
    {
        static $nullCache = null;

        if (!$nullCache instanceof CacheInterface) {
            $nullCache = new Repository(new NullStore());
        }

        return $nullCache;
    }

    /**
     * @param mixed $pk
     *
     * @return IlluminateModel|Model|mixed|null
     */
    public function findByPk($pk)
    {
        return $this->findByPks([$pk])->first();
    }

    /**
     * @param array|Arrayable|Traversable $pks
     *
     * @return KnightCollection
     */
    public function findByPks($pks): KnightCollection
    {
        return static::findByOneUniqueColumnValues(
            $this->getModel()->getKeyName(),
            Collection::make($pks)->filter(function ($value) {
                return $this->getModel()->isMatchPk($value);
            })
        );
    }

    public function findByOneUniqueColumnValues($column, $values): KnightCollection
    {
        $collection = Collection::make($values)->map(function ($value) use ($column) {
            return [$column => $value];
        });

        $rows = $this->findUniqueRows($collection->toArray())->keyBy($column);

        $collection = $this->getModel()->newCollection();
        foreach ($values as $value) {
            $row = $rows->get($value);
            if ($row instanceof IlluminateModel) {
                $collection->put($value, $row);
            }
        }

        return $collection;
    }

    /**
     * @param mixed $id
     *
     * @return IlluminateModel|Model|mixed|null
     */
    public function findUniqueRow($id)
    {
        return $this->findUniqueRows([$id])->first();
    }

    /**
     * 根据唯一建查找对象列表.
     *
     * @param array|Arrayable|Traversable $ids 必需是keyValue的格式, [['id' => 1, 'id2' => 1], ['id' => 1, 'id2' => 1]]
     *
     * @throws
     *
     * @return KnightCollection
     *
     * @phpstan-ignore-next-line
     */
    public function findUniqueRows($ids): KnightCollection
    {
        /** @var Collection $ids */
        $ids = Collection::make($ids)->values();
        $rows = $this->getModel()->newCollection();

        if ($ids->isEmpty()) {
            return $rows;
        }

        $cacheKeys = $ids->mapWithKeys(function ($id, $key) {
            return [$key => $this->getModel()->makeColumnsCacheKey($id)];
        });

        /** 缓存读取 */
        $missIndexes = Collection::make();
        $fromCacheRows = $this->getCache()->getMultiple($cacheKeys->toArray());
        foreach ($cacheKeys as $cacheKeyIndex => $cacheKey) {
            if (isset($fromCacheRows[$cacheKey]) && $fromCacheRows[$cacheKey] instanceof IlluminateModel) {
                if (method_exists($fromCacheRows[$cacheKey], 'setIsFromCache')) {
                    $fromCacheRows[$cacheKey]->setIsFromCache();
                }
                $rows->push($fromCacheRows[$cacheKey]);
                /** @codingStandardsIgnoreStart */
            } elseif (!isset($fromCacheRows[$cacheKey]) || !$this->getModel()->isCachePlaceholder($fromCacheRows[$cacheKey])) {
                /** @codingStandardsIgnoreEnd */
                $missIndexes->push($cacheKeyIndex);
            }
        }

        /** @phpstan-ignore-next-line */
        if ($missIndexes->isEmpty()) {
            return $rows->values();
        }

        /**
         * db 查询没有命中缓存的数据.
         *
         * [['pk1' => 1, 'pk2' => 1], ['pk1' => 1, 'pk2' => 1]] => ['pk1' => [1, 1], 'pk2' => [1, 1]]
         * [['pk1' => 1, 'pk2' => 1]] => ['pk1' => 1, 'pk2' => 1]
         */
        $missIds = $ids->only($missIndexes->toArray());
        $condition = Collection::make(array_merge_recursive(...$missIds->toArray()));
        $fromDbRows = $this
            ->where(function (self $query) use ($missIds, $condition) {
                /** 非联合唯一键 */
                if (1 === $condition->count()) {
                    foreach ($condition as $name => $values) {
                        if (is_array($values)) {
                            $query->whereIn($name, array_values(array_unique($values)));
                        } elseif (null === $values) {
                            $query->whereNull($name);
                        } else {
                            $query->where($name, $values);
                        }
                    }

                    return;
                }

                /** 联合唯一健, 但是只有一个In操作的 */
                if (1 === $condition->filter(fn ($v) => is_array($v) && 1 < count($v))->count()) {
                    foreach ($condition as $name => $values) {
                        if (is_array($values)) {
                            $query->whereIn($name, array_values(array_unique($values)));
                        } elseif (null === $values) {
                            $query->whereNull($name);
                        } else {
                            $query->where($name, $values);
                        }
                    }

                    return;
                }

                /** 兜底方案 */
                foreach ($missIds as $id) {
                    $query->orWhere(function (self $query) use ($id) {
                        foreach ($id as $name => $value) {
                            if (null === $value) {
                                $query->whereNull($name);
                            } else {
                                $query->where($name, $value);
                            }
                        }
                    });
                }
            })
            ->limit($missIndexes->count())
            /** @phpstan-ignore-next-line */
            ->get()->keyBy(function (IlluminateModel $model) use ($condition) {
                return $this->getModel()->makeColumnsCacheKey(
                    Arr::only($model->getAttributes(), $condition->keys()->toArray())
                );
            });

        /** 把db的查询结果缓存起来 */
        $cacheItems = [];
        foreach ($cacheKeys->only($missIndexes->toArray()) as $cacheKey) {
            if ($fromDbRows->has($cacheKey)) {
                $cacheItems[$cacheKey] = $fromDbRows->get($cacheKey);
            } elseif ($this->getModel()->hasCachePlaceholder()) {
                $cacheItems[$cacheKey] = $this->getModel()->getCachePlaceholder();
            }
        }
        if (!empty($cacheItems)) {
            $this->getCache()->setMultiple($cacheItems, $this->getModel()->getCacheTtl());
        }

        /** 合并db的查询结果 */
        foreach ($fromDbRows as $fromDbRow) {
            /** @phpstan-ignore-next-line */
            $rows->push($fromDbRow);
        }

        return $rows->values();
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    public function whereDeletedAtColumn($value = null)
    {
        if (null === $value) {
            return $this->whereNull($this->getModel()->getDeletedAtColumn());
        }

        return $this->whereNull($this->getModel()->getDeletedAtColumn(), $value);
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function whereLike(string $column, string $value)
    {
        return $this->where($column, 'LIKE', sprintf('%%%s%%', $value));
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function whereLeftLike(string $column, string $value)
    {
        return $this->where($column, 'LIKE', sprintf('%s%%', $value));
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function whereRightLike(string $column, string $value)
    {
        return $this->where($column, 'LIKE', sprintf('%%%s', $value));
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function orWhereLike(string $column, string $value)
    {
        return $this->orWhere($column, 'LIKE', sprintf('%%%s%%', $value));
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function orWhereLeftLike(string $column, string $value)
    {
        return $this->orWhere($column, 'LIKE', sprintf('%s%%', $value));
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function orWhereRightLike(string $column, string $value)
    {
        return $this->orWhere($column, 'LIKE', sprintf('%%%s', $value));
    }

    /**
     * @param bool|int     $when
     * @param ParameterBag $bag
     * @param string|int   $key
     * @param callable     $callable
     *
     * @return $this
     *
     * @deprecated
     */
    public function whenParameterBag($when, ParameterBag $bag, $key, callable $callable)
    {
        if ($when) {
            $this->where(function ($builder) use ($bag, $key, $callable) {
                $callable($builder, $bag->get($key));
            });
        }

        return $this;
    }

    /**
     * @param ParameterBag $bag
     * @param string|int   $key
     * @param callable     $callable
     *
     * @return $this
     *
     * @deprecated
     */
    public function whenParameterBagHas(ParameterBag $bag, $key, callable $callable)
    {
        return $this->whenParameterBag($bag->has($key), $bag, $key, $callable);
    }

    /**
     * @param ParameterBag $bag
     * @param string|int   $key
     * @param callable     $callable
     *
     * @return $this
     *
     * @deprecated
     */
    public function whenParameterBagNotHas(ParameterBag $bag, $key, callable $callable)
    {
        return $this->whenParameterBag(!$bag->has($key), $bag, $key, $callable);
    }

    /**
     * @param ParameterBag $bag
     * @param string|int   $key
     * @param callable     $callable
     *
     * @return $this
     *
     * @deprecated
     */
    public function whenParameterBagNull(ParameterBag $bag, $key, callable $callable)
    {
        return $this->whenParameterBag($bag->isNull($key), $bag, $key, $callable);
    }

    /**
     * @param ParameterBag $bag
     * @param string|int   $key
     * @param callable     $callable
     *
     * @return $this
     *
     * @deprecated
     */
    public function whenParameterBagNotNull(ParameterBag $bag, $key, callable $callable)
    {
        return $this->whenParameterBag(!$bag->isNull($key), $bag, $key, $callable);
    }

    /**
     * @param ParameterBag $bag
     * @param string|int   $key
     * @param callable     $callable
     *
     * @return $this
     *
     * @deprecated
     */
    public function whenParameterBagEmpty(ParameterBag $bag, $key, callable $callable)
    {
        return $this->whenParameterBag($bag->isEmpty($key), $bag, $key, $callable);
    }

    /**
     * @param ParameterBag $bag
     * @param string|int   $key
     * @param callable     $callable
     *
     * @return $this
     *
     * @deprecated
     */
    public function whenParameterBagNotEmpty(ParameterBag $bag, $key, callable $callable)
    {
        return $this->whenParameterBag(!$bag->isEmpty($key), $bag, $key, $callable);
    }

    /**
     * @param string   $column
     * @param iterable $values
     * @param string   $boolean
     * @param bool     $not
     *
     * @return $this
     */
    public function whereRange(string $column, iterable $values, string $boolean = 'and', bool $not = false)
    {
        if (is_array($values) && (isset($values['start']) || isset($values['end']))) {
            $values = [$values['start'] ?? null, $values['end'] ?? null];
        }

        $values = Collection::make(
            /** @phpstan-ignore-next-line */
            $values instanceof CarbonPeriod ? [$values->start, $values->end] : $values
        )->values()->slice(0, 2)->toArray();

        return $this->{$not ? 'whereNot' : 'where'}(function ($builder) use ($values, $column) {
            /** @var \HughCube\Laravel\Knight\Database\Eloquent\Builder $builder */
            if (isset($values[0])) {
                $builder->where($column, '>=', $values[0]);
            }

            if (isset($values[1])) {
                $builder->where($column, '<=', $values[1]);
            }
        }, null, null, $boolean);
    }

    /**
     * @param string   $column
     * @param iterable $values
     *
     * @return $this
     */
    public function orWhereRange(string $column, iterable $values)
    {
        return $this->whereRange($column, $values, 'or');
    }

    /**
     * @param string   $column
     * @param iterable $values
     *
     * @return $this
     */
    public function whereNotRange(string $column, iterable $values)
    {
        return $this->whereRange($column, $values, 'and', true);
    }

    /**
     * @param string   $column
     * @param iterable $values
     *
     * @return $this
     */
    public function orWhereNotRange(string $column, iterable $values)
    {
        return $this->whereRange($column, $values, 'or', true);
    }
}
