<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/6
 * Time: 10:12 下午.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use HughCube\Laravel\Knight\Database\Eloquent\Collection as KnightCollection;
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Ide\Database\Query\KIdeBuilder;
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
    protected $enableCache = true;

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
        if (!$this->enableCache) {
            return $this->getNullCache();
        }

        return $this->getModel()->getCache() ?? $this->getNullCache();
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
     * @return Model|null
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

        $collection = $this->getModel()->newCollection([]);
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
     * @return Model|null
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
        $ids = Collection::make($ids)->values();
        $rows = $this->getModel()->newCollection();

        if ($ids->isEmpty()) {
            return $rows;
        }

        $cacheKeys = $ids->mapWithKeys(function ($id, $key) {
            return [$key => $this->getModel()->makeColumnsCacheKey($id)];
        });

        /** 缓存读取 */
        $missIndexes = Collection::make([]);
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
        if ($missIndexes->isEmpty()) {
            return $rows->values();
        }

        /** db 查询没有命中缓存的数据 */
        /** [['pk1' => 1, 'pk2' => 1], ['pk1' => 1, 'pk2' => 1]] => ['pk1' => [1, 1], 'pk2' => [1, 1]] */
        /** [['pk1' => 1, 'pk2' => 1]] => ['pk1' => 1, 'pk2' => 1] */
        $condition = Collection::make(array_merge_recursive(...$ids->only($missIndexes->toArray())->toArray()));
        $fromDbRows = $this
            ->where(
                function (self $query) use ($condition) {
                    foreach ($condition as $name => $values) {
                        if (is_array($values)) {
                            $query->whereIn($name, array_values(array_unique($values)));
                        } elseif (null === $values) {
                            $query->whereNull($name);
                        } else {
                            $query->where($name, $values);
                        }
                    }
                }
            )
            ->limit($missIndexes->count())
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
        return $this->where($column, 'LIKE', "%$value%");
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function whereLeftLike(string $column, string $value)
    {
        return $this->where($column, 'LIKE', "$value%");
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function whereRightLike(string $column, string $value)
    {
        return $this->where($column, 'LIKE', "%$value");
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function orWhereLike(string $column, string $value)
    {
        return $this->orWhere($column, 'LIKE', "%$value%");
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function orWhereLeftLike(string $column, string $value)
    {
        return $this->orWhere($column, 'LIKE', "$value%");
    }

    /**
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function orWhereRightLike(string $column, string $value)
    {
        return $this->orWhere($column, 'LIKE', "%$value");
    }
}
