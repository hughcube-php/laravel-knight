<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Carbon\Carbon as BaseCarbon;
use DateTime;
use Exception;
use HughCube\Laravel\Knight\Database\Eloquent\Builder;
use HughCube\Laravel\Knight\Support\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Traversable;

/**
 * Trait QueryCache.
 *
 * @method static Builder query()
 * @method static Builder newQuery()
 */
trait Model
{
    /**
     * @var bool
     */
    private $isFromCache = false;

    /**
     * @param null|string|DateTime|int $date
     *
     * @return Carbon|null
     */
    public function toDateTime($date = null): ?Carbon
    {
        return empty($date) ? null : Carbon::parse($date);
    }

    /**
     * @param null|DateTime|BaseCarbon $dateTime
     * @param string                   $format
     *
     * @return string|null
     */
    public function formatDateTime($dateTime, string $format = 'Y-m-d H:i:s'): ?string
    {
        return $dateTime instanceof BaseCarbon ? $dateTime->format($format) : null;
    }

    /**
     * @param mixed $date
     *
     * @return Carbon|null
     */
    public function getCreatedAtAttribute($date): ?Carbon
    {
        return $this->toDateTime($date);
    }

    /**
     * @param mixed $date
     *
     * @return Carbon|null
     */
    public function getUpdatedAtAttribute($date): ?Carbon
    {
        return $this->toDateTime($date);
    }

    /**
     * @param mixed $date
     *
     * @return Carbon|null
     */
    public function getDeletedAtAttribute($date): ?Carbon
    {
        return $this->toDateTime($date);
    }

    /**
     * @param string $format
     *
     * @return string|null
     */
    public function formatCreatedAt(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->formatDateTime($this->{$this->getCreatedAtColumn()}, $format);
    }

    /**
     * @param string $format
     *
     * @return string|null
     */
    public function formatUpdatedAt(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->formatDateTime($this->{$this->getUpdatedAtColumn()}, $format);
    }

    /**
     * @param string $format
     *
     * @return string|null
     */
    public function formatDeleteAt(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->formatDateTime($this->{$this->getDeletedAtColumn()}, $format);
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

        if (is_numeric($deletedAt) && 0 == $deletedAt) {
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
     * @param \Illuminate\Database\Query\Builder $query
     *
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
     * @return CacheInterface|null
     */
    public function getCache(): ?CacheInterface
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
     * @param int|null $duration
     *
     * @throws Exception
     *
     * @return int
     */
    public function getCacheTtl(int $duration = null): int
    {
        return null === $duration ? random_int((5 * 24 * 3600), (7 * 24 * 3600)) : $duration;
    }

    /**
     * @return string|null
     */
    public function getCacheVersion(): ?string
    {
        return '';
    }

    /**
     * @param mixed $id
     *
     * @throws InvalidArgumentException
     *
     * @return static|null
     */
    public static function findById($id)
    {
        return static::findByIds([$id])->first();
    }

    /**
     * @param array|Arrayable|Traversable $ids
     *
     * @throws InvalidArgumentException
     *
     * @return Collection
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

    /**
     * @throws InvalidArgumentException
     */
    public function refreshRowCache(): bool
    {
        return $this->newQuery()->refreshRowCache();
    }

    public function isMatchPk($value): bool
    {
        return !empty($value);
    }

    /**
     * @return string|null
     */
    public function getCachePlaceholder(): ?string
    {
        return '@@fad7563e68d@@';
    }
}
