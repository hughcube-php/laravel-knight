<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/6
 * Time: 10:12 下午
 */

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Model as IlluminateModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\CacheInterface;

/**
 * Trait Builder
 * @method Model getModel()
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
    protected function getCache()
    {
        if (!$this->enableCache) {
            return $this->getNullCache();
        }

        if (!method_exists($this->getModel(), 'getCache')) {
            return $this->getNullCache();
        }

        $cache = $this->getModel()->getCache();
        $cache = is_string($cache) ? Cache::store($cache) : $cache;
        return $cache instanceof CacheInterface ? $cache : $this->getNullCache();
    }

    /**
     * @return CacheInterface
     */
    protected function getNullCache()
    {
        if (!self::$nullCache instanceof CacheInterface) {
            self::$nullCache = new Repository(new NullStore());
        }
        return self::$nullCache;
    }

    /**
     * @return string
     */
    protected function getCachePlaceholder()
    {
        return 'Ah2XeR6g@@NULL@@iehee2Oe';
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isCachePlaceholder($value)
    {
        return $value === $this->getCachePlaceholder();
    }

    /**
     * @param array $columns
     * @return string
     */
    protected function makeColumnsCacheKey(array $columns)
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
    protected function makePkCacheKey()
    {
        return $this->makeColumnsCacheKey([
            $this->getModel()->getKeyName() => $this->getModel()->getKey()
        ]);
    }

    /**
     * @param integer $pk
     * @return Model
     */
    public function findByPk($pk)
    {
        $collection = $this->findByPks([$pk]);

        return $collection->get($pk);
    }

    /**
     * @param integer[] $pks
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByPks($pks)
    {
        $collection = Collection::make($pks)->map(function ($value) {
            return [$this->getModel()->getKeyName() => $value];
        });

        $rows = $this->findAllByUniqueColumn($collection->toArray())
            ->keyBy($this->getModel()->getKeyName());

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
     * @param array[] $ids 必需是keyValue的格式, [['id' => 1], ['id' => 1]]
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findAllByUniqueColumn(array $ids)
    {
        /** @var \Illuminate\Database\Eloquent\Collection $rows */
        $rows = $this->getModel()->newCollection([]);

        if (empty($ids)) {
            return $rows;
        }

        /** @var Collection $ids */
        $ids = Collection::make($ids)->values();

        /** @var Collection $cacheKeys */
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
        $condition = Collection::make(array_merge_recursive(...$ids->only($missIndexes->toArray())));
        $fromDbRows = $this
            ->where(function (self $query) use ($condition) {
                foreach ($condition as $name => $values) {
                    $query->where($name, array_values(array_unique((array)$values)));
                }
            })
            ->limit(count($missIndexes))
            ->get()
            ->keyBy(function (IlluminateModel $model) use ($condition) {
                return $this->makeColumnsCacheKey(Arr::only($model->getAttributes(), $condition->keys()->toArray()));
            });

        /** 把db的查询结果缓存起来 */
        $cacheItems = [];
        foreach ($cacheKeys->only($missIndexes->toArray()) as $cacheKey) {
            if ($fromDbRows->get($cacheKey)) {
                $cacheItems[$cacheKey] = $fromDbRows->get($cacheKey);
            } else {
                $cacheItems[$cacheKey] = $this->getCachePlaceholder();
            }
        }
        if (!empty($cacheItems)) {
            $this->getCache()->setMultiple($cacheItems, $this->getModel()->getCacheTtl());
        }

        /** 合并db的查询结果 */
        $rows->push(...$fromDbRows->values()->all());

        return $rows->values();
    }
}
