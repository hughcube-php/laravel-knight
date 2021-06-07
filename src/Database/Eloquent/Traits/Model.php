<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Carbon\Carbon;
use HughCube\Laravel\Knight\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\CacheInterface;

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
     * @var boolean
     */
    protected $isFromCache = false;

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
     * Create a new Eloquent query builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * 跳过缓存执行.
     * @return Builder
     */
    public static function noCacheQuery()
    {
        return static::query()->noCache();
    }

    /**
     * 获取缓存.
     *
     * @return CacheInterface|string|null
     */
    public function getCache()
    {
        if (!defined('static::CACHE')) {
            return null;
        }
        return Cache::store(constant('static::DELETED_AT'));
    }

    /**
     * @return bool
     */
    public function isFromCache()
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
     * @param int|null $duration
     *
     * @return int|null
     */
    public function getCacheTtl($duration = null)
    {
        return null === $duration ? random_int((5 * 24 * 3600), (7 * 24 * 3600)) : null;
    }

    /**
     * @return string|null
     */
    public function getCacheVersion()
    {
        return $this->cacheVersion;
    }

    /**
     * @param integer $id
     * @return static
     */
    public static function findById($id)
    {
        /** @var static $row */
        $row = static::query()->findByPk($id);

        return $row;
    }

    /**
     * @param integer[] $ids
     * @return static[]|\Illuminate\Database\Eloquent\Collection
     */
    public static function findByIds($ids)
    {
        return static::query()->findByPks($ids);
    }

    /**
     * @return array[]
     */
    public function onChangeRefreshCacheKeys()
    {
        return [
            [$this->getKeyName() => $this->getKey()]
        ];
    }
}
