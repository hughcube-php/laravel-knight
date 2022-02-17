<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Carbon\Carbon;
use Exception;
use HughCube\Laravel\Knight\Database\Eloquent\Builder;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Trait QueryCache.
 *
 * @method static Builder query()
 */
trait Model
{
    /**
     * @var string|null
     */
    protected $cacheVersion;

    /**
     * @var bool
     */
    protected $isFromCache = false;

    /**
     * @param  mixed|null  $date
     *
     * @return Carbon|null
     */
    public function toCarbon($date = null)
    {
        return empty($date) ? null : Carbon::parse($date);
    }

    /**
     * @param  mixed  $date
     *
     * @return Carbon|null
     */
    public function getCreatedAtAttribute($date)
    {
        return $this->toCarbon($date);
    }

    /**
     * @param  mixed  $date
     *
     * @return Carbon|null
     */
    public function getUpdatedAtAttribute($date)
    {
        return $this->toCarbon($date);
    }

    /**
     * @param  mixed  $date
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
    public function isDeleted(): bool
    {
        $deletedAt = $this->getAttribute($this->getDeletedAtColumn());

        if (null === $deletedAt) {
            return false;
        }

        if (0 == $deletedAt) {
            return false;
        }

        return true;
    }

    /**
     * 判断数据是否正常.
     *
     * @return bool
     */
    public function isNormal(): bool
    {
        return false == $this->isDeleted();
    }

    /**
     * Get the name of the "deleted at" column.
     *
     * @return string
     */
    public function getDeletedAtColumn(): string
    {
        return defined('static::DELETED_AT') ? constant('static::DELETED_AT') : 'deleted_at';
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return Builder
     */
    public function newEloquentBuilder($query): Builder
    {
        return new Builder($query);
    }

    /**
     * 跳过缓存执行.
     *
     * @return Builder
     */
    public static function noCacheQuery(): Builder
    {
        return static::query()->noCache();
    }

    /**
     * 获取缓存.
     *
     * @return Repository|null
     */
    public function getCache()
    {
        if (!defined('static::CACHE')) {
            return null;
        }

        return Cache::store(constant('static::CACHE'));
    }

    /**
     * @return bool
     */
    public function isFromCache(): bool
    {
        return $this->isFromCache;
    }

    /**
     * @return $this
     */
    public function setIsFromCache($is = true)
    {
        $this->isFromCache = $is;
        return $this;
    }

    /**
     * 缓存的时间, 默认5-7天.
     *
     * @param  int|null  $duration
     * @return int
     * @throws Exception
     */
    public function getCacheTtl(int $duration = null): int
    {
        return null === $duration ? random_int((5 * 24 * 3600), (7 * 24 * 3600)) : $duration;
    }

    /**
     * @return string|null
     */
    public function getCacheVersion()
    {
        return $this->cacheVersion;
    }

    /**
     * @param  mixed  $id
     * @return static
     * @throws InvalidArgumentException
     */
    public static function findById($id)
    {
        return static::query()->findByPk($id);
    }

    /**
     * @param  array|Collection  $ids
     *
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function findByIds($ids): Collection
    {
        return static::query()->findByPks($ids);
    }

    /**
     * @return array[]
     */
    public function onChangeRefreshCacheKeys(): array
    {
        return [
            [$this->getKeyName() => $this->getKey()],
        ];
    }
}
