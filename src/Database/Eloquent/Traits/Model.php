<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Psr\SimpleCache\CacheInterface;

/**
 * Trait QueryCache.
 */
trait Model
{
    /**
     * @var bool 是否来自缓存
     */
    private $isFromCache = false;

    /**
     * @var bool 是否跳过缓存
     */
    private static $skipCache = false;

    /**
     * @param mixed $date
     *
     * @return Carbon|null
     */
    public function toCarbon($date = null)
    {
        return empty($date) ? null : Carbon::parse($date);
    }

    /**
     * @param mixed $date
     *
     * @return Carbon|null
     */
    public function getCreatedAtAttribute($date)
    {
        return $this->toCarbon($date);
    }

    /**
     * @param mixed $date
     *
     * @return Carbon|null
     */
    public function getUpdatedAtAttribute($date)
    {
        return $this->toCarbon($date);
    }

    /**
     * @param mixed $date
     *
     * @return Carbon|null
     */
    public function getDeleteAtAttribute($date)
    {
        return $this->toCarbon($date);
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        $deletedAt = $this->getAttribute($this->getDeletedAtColumn());

        return null === $deletedAt || (is_numeric($deletedAt) && 0 == $deletedAt);
    }

    /**
     * 判断数据是否正常.
     *
     * @return bool
     */
    public function isNormal()
    {
        return false == $this->isDeleted();
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? constant('static::DELETED_AT') : 'deleted_at';
    }

    /**
     * 跳过缓存执行.
     *
     * @param \Closure $callback 执行的回调
     *
     * @return mixed
     */
    public static function noCache(Closure $callback)
    {
        $originalSkipCache = static::$skipCache;
        static::$skipCache = true;

        try {
            $result = $callback();
        } finally {
            static::$skipCache = $originalSkipCache;
        }

        return $result;
    }

    /**
     * 获取缓存.
     *
     * @return CacheInterface|null;
     */
    public function getCache()
    {
        return null;
    }

    /**
     * 标识对象是否是通过缓存查找出来的.
     *
     * @return bool
     */
    public function isFromCache()
    {
        return true == $this->isFromCache;
    }

    /**
     * 缓存的时间, 默认5-7天.
     *
     * @param int|null $duration
     *
     * @return int|null
     */
    public function getCacheDuration($duration = null)
    {
        return null === $duration ? random_int((5 * 24 * 3600), (7 * 24 * 3600)) : null;
    }

    /**
     * 缓存版本控制.
     *
     * @return string
     */
    public function getCacheVersion()
    {
        return '1.0';
    }

    protected function getCachePlaceholder()
    {
        return 'Ah2XeR6g@@NULL@@iehee2Oe';
    }

    protected function isCachePlaceholder($value)
    {
        return $value === $this->getCachePlaceholder();
    }

    /**
     * @param array $columns
     *
     * @return string
     */
    protected function buildColumnsCacheKey(array $columns)
    {
        $cacheKey = [];
        foreach ($columns as $name => $value) {
            $name = is_numeric($name) ? $this->getKey() : $name;
            $cacheKey[((string) $name)] = ((string) $value);
        }

        ksort($cacheKey);
        $cacheKey = json_encode($cacheKey);

        return 'model:'.md5(sprintf('%s:%s:%s', static::class, $cacheKey, $this->getCacheVersion()));
    }

    protected function buildPrimaryKeyCacheKey()
    {
        $keyName = $this->getKeyName();

        return $this->buildColumnsCacheKey([$keyName => $this->{$keyName}]);
    }

    /**
     * @param string $cacheKey
     * @param mixed  $default
     *
     * @return mixed|null
     */
    public function cacheGet($cacheKey, $default = null)
    {
        /** @var CacheInterface|null $cache */
        $cache = static::$skipCache ? null : $this->getCache();

        if ($cache instanceof CacheInterface) {
            $value = $cache->get($cacheKey, $this->getCachePlaceholder());

            return $this->isCachePlaceholder($value) ? $default : $value;
        }

        return $default;
    }

    /**
     * @param array $cacheKeys
     *
     * @return array|iterable
     */
    public function cacheGetMultiple(array $cacheKeys)
    {
        if (empty($cacheKeys)) {
            return [];
        }

        /** @var CacheInterface $cache */
        $cache = static::$skipCache ? null : $this->getCache();

        $values = [];
        if ($cache instanceof CacheInterface) {
            $values = $cache->getMultiple(array_values($cacheKeys));
        }

        foreach ($values as $key => $value) {
            if ($this->isCachePlaceholder($value)) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /**
     * @param string          $cacheKey
     * @param mixed           $value
     * @param null|int|Carbon $duration
     *
     * @return bool
     */
    public function cacheSet($cacheKey, $value, $duration = null)
    {
        /** @var CacheInterface|null $cache */
        $cache = static::$skipCache ? null : $this->getCache();

        if ($cache instanceof CacheInterface) {
            return true == $cache->set($cacheKey, $value, $this->getCacheDuration($duration));
        }

        return true;
    }

    /**
     * 批量缓存数据.
     *
     * @param array    $items
     * @param int|null $duration
     *
     * @return bool
     */
    final public function cacheSetMultiple(array $items, $duration = null)
    {
        if (empty($items)) {
            return true;
        }

        /** @var CacheInterface|null $cache */
        $cache = static::$skipCache ? null : $this->getCache();

        if ($cache instanceof CacheInterface) {
            return true == $cache->setMultiple($items, $this->getCacheDuration($duration));
        }

        return true;
    }

    /**
     * 批量删除缓存.
     *
     * @param array $cacheKeys
     *
     * @return bool
     */
    public function cacheDeleteMultiple(array $cacheKeys)
    {
        if (empty($cacheKeys)) {
            return true;
        }

        /** @var CacheInterface|null $cache */
        $cache = static::$skipCache ? null : $this->getCache();

        if ($cache instanceof CacheInterface) {
            return $cache->deleteMultiple(array_values($cacheKeys));
        }

        return true;
    }

    /**
     * 从缓存里面获取数据, 如果不存在把 $callable 的返回值缓存起来.
     *
     * @param string   $cacheKey
     * @param callable $callable
     * @param int|null $duration
     *
     * @return mixed
     */
    public function cacheGetOrSet($cacheKey, $callable, $duration = null)
    {
        $value = $this->cacheGet($cacheKey);
        if (null != $value) {
            return $value;
        }

        $value = $callable();

        $this->cacheSet($cacheKey, $value, $duration);

        return $value;
    }

    /**
     * 重置当前数据的缓存.
     *
     * @return bool
     */
    public function resetCache()
    {
        return $this->cacheSet($this->buildPrimaryKeyCacheKey(), $this);
    }

    /**
     * 删除当前对象对应的 cache.
     *
     * @return bool
     */
    public function deleteCache()
    {
        return $this->cacheDeleteMultiple([$this->buildPrimaryKeyCacheKey()]);
    }

    protected function populateCacheRow()
    {
        $this->isFromCache = true;

        return $this;
    }

    /**
     * Find rows by pk.
     *
     * @param array|Collection $ids
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function findByIds($ids)
    {
        /** @var static $model */
        $model = static::query()->getModel();

        $keyName = $model->getKeyName();

        $collection = Collection::make($ids)->map(function ($value) use ($keyName) {
            return [$keyName => $value];
        });

        $rows = $model->findAllByUniqueColumn($collection->toArray())->keyBy($keyName);

        $collection = $model->newCollection([]);
        foreach ($ids as $id) {
            $row = $rows->get($id);
            if ($row instanceof static) {
                $collection->put($id, $row);
            }
        }

        return $collection;
    }

    /**
     * Find row by pk.
     *
     * @param int $id
     *
     * @return static
     */
    public static function findById($id)
    {
        $collection = static::findByIds([$id]);

        return $collection->get($id);
    }

    /**
     * 根据唯一建查找对象列表.
     *
     * @param array[]  $ids      必需是keyValue的格式, [['id' => 1], ['id' => 1]]
     * @param int|null $duration 缓存的有效期, 单位秒
     *
     * @return Collection
     */
    protected function findAllByUniqueColumn(array $ids, $duration = null)
    {
        $rows = $this->newCollection([]);

        if (empty($ids)) {
            return $rows;
        }

        /** @var Collection $ids */
        $ids = Collection::make($ids)->values();

        /** @var Collection $cacheKeys */
        $cacheKeys = $ids->mapWithKeys(function ($id, $key) {
            return [$key => $this->buildColumnsCacheKey($id)];
        });

        /** 缓存读取 */
        $missIndexes = $this->addRowsByCacheKeys($cacheKeys, $rows);
        if ($missIndexes->isEmpty()) {
            return $rows->values();
        }

        /** db 查询没有命中缓存的数据 */
        /** [['pk1' => 1, 'pk2' => 1], ['pk1' => 1, 'pk2' => 1]] => ['pk1' => [1, 1], 'pk2' => [1, 1]] */
        $condition = Collection::make(array_merge_recursive(...$ids->only($missIndexes->toArray())));
        $fromDbRows = $this->newQuery()
            ->where(function (Builder $query) use ($condition) {
                foreach ($condition as $column => $values) {
                    $query->whereIn($column, array_values(array_unique((array) $values)));
                }
            })
            ->limit(count($missIndexes))
            ->get()->keyBy(function (self $model) use ($condition) {
                return $this->buildColumnsCacheKey(Arr::only($model->getAttributes(), $condition->keys()->toArray()));
            });

        /** 把db的查询结果缓存起来 */
        $this->cacheRowsByCacheKeys($cacheKeys->only($missIndexes->toArray()), $fromDbRows, $duration);

        /** 合并db的查询结果 */
        foreach ($fromDbRows as $fromDbRow) {
            $rows->add($fromDbRow);
        }

        return $rows->values();
    }

    protected function cacheRowsByCacheKeys(Collection $cacheKeys, Collection $rows, $duration)
    {
        $cacheItems = [];
        foreach ($cacheKeys as $cacheKey) {
            if ($rows->has($cacheKey)) {
                $cacheItems[$cacheKey] = $rows->get($cacheKey);
            } else {
                $cacheItems[$cacheKey] = $this->getCachePlaceholder();
            }
        }

        if (!empty($cacheItems)) {
            $this->cacheSetMultiple($cacheItems, $duration);
        }
    }

    /**
     * @param Collection $cacheKeys
     * @param Collection $rows
     *
     * @return Collection
     */
    protected function addRowsByCacheKeys(Collection $cacheKeys, Collection $rows)
    {
        $missIndexes = Collection::make([]);

        $fromCacheRows = $this->cacheGetMultiple($cacheKeys->toArray());

        foreach ($cacheKeys as $cacheKeyIndex => $cacheKey) {
            if (isset($fromCacheRows[$cacheKey])) {
                $rows->add($fromCacheRows[$cacheKey]->populateCacheRow());
            } elseif (!isset($fromCacheRows[$cacheKey]) || !$this->isCachePlaceholder($fromCacheRows[$cacheKey])) {
                $missIndexes->add($cacheKeyIndex);
            }
        }

        return $missIndexes;
    }
}
