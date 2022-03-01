<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Carbon\Carbon as BaseCarbon;
use DateTime;
use HughCube\Laravel\Knight\Database\Eloquent\Builder;
use HughCube\Laravel\Knight\Support\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Psr\SimpleCache\CacheInterface;
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
        $cache = defined('static::CACHE') ? constant('static::CACHE') : null;
        if (false === $cache) {
            return null;
        }

        return Cache::store($cache);
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
     * @throws
     *
     * @return int
     * @phpstan-ignore-next-line
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
        return 'v1.0.0';
    }

    /**
     * @param mixed $id
     *
     * @return mixed
     */
    public static function findById($id)
    {
        return static::findByIds([$id])->first();
    }

    /**
     * @param array|Arrayable|Traversable $ids
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
     * @return bool
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

    public function genModelVersion(): int
    {
        return crc32(serialize([Str::random(100), microtime()]));
    }

    /**
     * 是否可用的数据.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return !$this->isDeleted();
    }
}
