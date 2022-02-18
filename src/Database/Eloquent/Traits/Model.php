<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Carbon\Carbon;
use Exception;
use HughCube\Laravel\Knight\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use JetBrains\PhpStorm\Pure;
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
    private bool $isFromCache = false;

    /**
     * @param  mixed|null  $date
     *
     * @return Carbon|null
     */
    public function toCarbon(mixed $date = null): ?Carbon
    {
        return empty($date) ? null : Carbon::parse($date);
    }

    /**
     * @param  mixed  $date
     *
     * @return Carbon|null
     */
    public function getCreatedAtAttribute(mixed $date): ?Carbon
    {
        return $this->toCarbon($date);
    }

    /**
     * @param  mixed  $date
     *
     * @return Carbon|null
     */
    public function getUpdatedAtAttribute(mixed $date): ?Carbon
    {
        return $this->toCarbon($date);
    }

    /**
     * @param  mixed  $date
     *
     * @return Carbon|null
     */
    public function getDeleteAtAttribute(mixed $date): ?Carbon
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
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return Builder
     */
    #[Pure]
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
    public function setIsFromCache($is = true): static
    {
        $this->isFromCache = $is;

        return $this;
    }

    /**
     * 缓存的时间, 默认5-7天.
     *
     * @param  int|null  $duration
     *
     * @return int
     * @throws Exception
     *
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
     * @param  mixed  $id
     * @return static|null
     * @throws InvalidArgumentException
     */
    public static function findById(mixed $id): ?static
    {
        return static::findByIds([$id])->first();
    }

    /**
     * @param  array|Arrayable|Traversable  $ids
     * @return Collection
     * @throws InvalidArgumentException
     */
    public static function findByIds(array|Arrayable|Traversable $ids): Collection
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

    public function isMatchPk(mixed $value): bool
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
