<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/6
 * Time: 10:12 下午.
 */

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Exception;
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
    /**
     * @var bool
     */
    protected bool $enableCache = true;

    /**
     * @return static
     */
    public function noCache(): static
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
        static $nullCache = null;

        if (!$nullCache instanceof CacheInterface) {
            $nullCache = new Repository(new NullStore());
        }
        return $nullCache;
    }

    /**
     * @return string|null
     */
    protected function getCachePlaceholder(): ?string
    {
        if (method_exists($this->getModel(), 'getCachePlaceholder')) {
            return $this->getModel()->getCachePlaceholder();
        }

        return null;
    }

    /**
     * @return bool
     */
    protected function hasCachePlaceholder(): bool
    {
        return null !== $this->getCachePlaceholder();
    }

    /**
     * @param  mixed  $value
     *
     * @return bool
     */
    protected function isCachePlaceholder(mixed $value): bool
    {
        return $value === $this->getCachePlaceholder() && $this->hasCachePlaceholder();
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
     * @param  mixed  $pk
     * @return static|null
     * @throws InvalidArgumentException
     */
    public function findByPk(mixed $pk): static|null
    {
        $collection = $this->findByPks((empty($pk) ? [] : [$pk]));
        return $collection->get($pk);
    }

    /**
     * @param  array  $pks
     * @return EloquentCollection
     * @throws InvalidArgumentException
     */
    public function findByPks(array $pks): EloquentCollection
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
     * @throws InvalidArgumentException
     */
    public function findUniqueRow(mixed $id)
    {
        $rows = $this->findUniqueRows([$id]);
        return $rows->isEmpty() ? null : $rows->first();
    }

    /**
     * 根据唯一建查找对象列表.
     *
     * @param  array[]  $ids  必需是keyValue的格式, [['id' => 1, 'id2' => 1], ['id' => 1, 'id2' => 1]]
     *
     * @return EloquentCollection
     * @throws InvalidArgumentException
     * @throws Exception
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
            if (isset($fromCacheRows[$cacheKey]) && $fromCacheRows[$cacheKey] instanceof IlluminateModel) {
                if (method_exists($fromCacheRows[$cacheKey], 'setIsFromCache')) {
                    $fromCacheRows[$cacheKey]->setIsFromCache();
                }
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
            } elseif ($this->hasCachePlaceholder()) {
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
        $results = parent::delete();
        if (false !== $results) {
            $this->refreshRowCache();
        }

        return $results;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function update(array $values)
    {
        /** @var int|bool $results */
        $results = parent::update($values);
        if (false !== $results && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $results;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function insert(array $values)
    {
        /** @var int|bool $results */
        $results = parent::insert($values);
        if (false !== $results && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $results;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function insertOrIgnore(array $values)
    {
        /** @var int|bool $results */
        $results = parent::insertOrIgnore($values);
        if (false !== $results && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $results;
    }

    /**
     * @inheritdoc
     * @throws InvalidArgumentException
     */
    public function insertGetId(array $values, $sequence = null)
    {
        /** @var int|bool $results */
        $results = parent::insertGetId($values, $sequence);
        if (false !== $results && $this->getModel()->exists) {
            $this->refreshRowCache();
        }

        return $results;
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
