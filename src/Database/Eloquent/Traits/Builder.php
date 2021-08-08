<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/6
 * Time: 10:12 下午.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Trait Builder.
 *
 * @method Model getModel()
 * @method Connection getConnection()
 */
trait Builder
{
    private static $nullCache;

    /**
     * @var bool
     */
    protected $enableCache = true;

    /**
     * @return static
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

        if (!method_exists($this->getModel(), 'getCache')) {
            return $this->getNullCache();
        }

        $cache = $this->getModel()->getCache();

        return $cache instanceof CacheInterface ? $cache : $this->getNullCache();
    }

    /**
     * @return CacheInterface
     */
    protected function getNullCache(): CacheInterface
    {
        if (!self::$nullCache instanceof CacheInterface) {
            self::$nullCache = new Repository(new NullStore());
        }
        return self::$nullCache;
    }

    /**
     * @return string
     */
    protected function getCachePlaceholder(): string
    {
        return '@@fad7563e68d@@';
    }

    /**
     * @param  mixed  $value
     *
     * @return bool
     */
    protected function isCachePlaceholder($value): bool
    {
        return $value === $this->getCachePlaceholder();
    }

    /**
     * @param  array  $columns
     *
     * @return string
     */
    protected function makeColumnsCacheKey(array $columns): string
    {
        $cacheKey = [];
        foreach ($columns as $name => $value) {
            $name = is_numeric($name) ? $this->getModel()->getKeyName() : $name;
            $cacheKey[strval($name)] = (strval($value));
        }

        ksort($cacheKey);
        $cacheKey = json_encode($cacheKey);

        $string = sprintf('%s:%s:%s', get_class($this->getModel()), $cacheKey, $this->getModel()->getCacheVersion());

        return sprintf('model:%s-%s', md5($string), crc32($string));
    }

    /**
     * @return string
     */
    protected function makePkCacheKey(): string
    {
        return $this->makeColumnsCacheKey([
            $this->getModel()->getKeyName() => $this->getModel()->getKey(),
        ]);
    }

    /**
     * @param  mixed  $pk
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function findByPk($pk)
    {
        $collection = $this->findByPks([$pk]);
        return $collection->get($pk);
    }

    /**
     * @param  array|Collection  $pks
     *
     * @return EloquentCollection
     * @throws InvalidArgumentException
     */
    public function findByPks($pks): EloquentCollection
    {
        $collection = Collection::make($pks)->map(function ($value) {
            return [$this->getModel()->getKeyName() => $value];
        });

        $rows = $this->findUniqueRows($collection->toArray())->keyBy($this->getModel()->getKeyName());

        $collection = $this->getModel()->newCollection([]);
        foreach ($pks as $pk) {
            $row = $rows->get($pk);
            if ($row instanceof IlluminateModel) {
                $collection->put($pk, $row);
            }
        }
        return $collection;
    }

    /**
     * 根据唯一建查找对象列表.
     *
     * @param  array[]  $ids  必需是keyValue的格式, [['id' => 1, 'id2' => 1], ['id' => 1, 'id2' => 1]]
     *
     * @return EloquentCollection
     * @throws InvalidArgumentException
     */
    public function findUniqueRows(array $ids): EloquentCollection
    {
        $rows = $this->getModel()->newCollection([]);

        if (empty($ids)) {
            return $rows;
        }

        /** @var Collection $ids */
        $ids = Collection::make($ids)->values();

        $cacheKeys = $ids->mapWithKeys(function ($id, $key) {
            return [$key => $this->makeColumnsCacheKey($id)];
        });

        /** 缓存读取 */
        $missIndexes = Collection::make([]);
        $fromCacheRows = $this->getCache()->getMultiple($cacheKeys->toArray());
        foreach ($cacheKeys as $cacheKeyIndex => $cacheKey) {
            if (isset($fromCacheRows[$cacheKey]) && $fromCacheRows[$cacheKey] instanceof Model) {
                $rows->push($fromCacheRows[$cacheKey]->setIsFromCache());
            } elseif (isset($fromCacheRows[$cacheKey]) && $fromCacheRows[$cacheKey] instanceof IlluminateModel) {
                $rows->push($fromCacheRows[$cacheKey]);
            } elseif (!isset($fromCacheRows[$cacheKey]) || !$this->isCachePlaceholder($fromCacheRows[$cacheKey])) {
                $missIndexes->push($cacheKeyIndex);
            }
        }
        if ($missIndexes->isEmpty()) {
            return $rows->values();
        }

        /** db 查询没有命中缓存的数据 */
        /** [['pk1' => 1, 'pk2' => 1], ['pk1' => 1, 'pk2' => 1]] => ['pk1' => [1, 1], 'pk2' => [1, 1]] */
        /** [['pk1' => 1, 'pk2' => 1]] => ['pk1' => 1, 'pk2' => 1] */
        $condition = Collection::make(array_merge_recursive(...$ids->only($missIndexes->toArray())));
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
            ->limit(count($missIndexes))
            ->get()
            ->keyBy(function (IlluminateModel $model) use ($condition) {
                return $this->makeColumnsCacheKey(Arr::only($model->getAttributes(), $condition->keys()->toArray()));
            });

        /** 把db的查询结果缓存起来 */
        $cacheItems = [];
        foreach ($cacheKeys->only($missIndexes->toArray()) as $cacheKey) {
            if ($fromDbRows->has($cacheKey)) {
                $cacheItems[$cacheKey] = $fromDbRows->get($cacheKey);
            } else {
                $cacheItems[$cacheKey] = $this->getCachePlaceholder();
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
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function delete()
    {
        $number = parent::delete();
        if (false !== $number && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $number;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function update(array $values): int
    {
        $number = parent::update($values);
        if (false < $number && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $number;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function insert(array $values)
    {
        $number = parent::insert($values);
        if (false !== $number && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $number;
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    public function refreshRowCache(): bool
    {
        $cacheKeys = Collection::make($this->getModel()->onChangeRefreshCacheKeys())
            ->mapWithKeys(function ($id, $key) {
                return [$key => $this->makeColumnsCacheKey($id)];
            });

        return $this->getCache()->deleteMultiple($cacheKeys->values()->toArray());
    }
}
