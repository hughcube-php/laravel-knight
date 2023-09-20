<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use DateTimeInterface;
use HughCube\Base\Base;
use HughCube\Laravel\Knight\Database\Eloquent\Builder;
use HughCube\Laravel\Knight\Database\Eloquent\Collection as KnightCollection;
use HughCube\Laravel\Knight\Support\Carbon;
use HughCube\Laravel\Knight\Support\Json;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Str;
use Psr\SimpleCache\CacheInterface;
use Traversable;

/**
 * Trait QueryCache.
 *
 * @method static Builder query()
 * @method static Builder newQuery()
 *
 * @mixin SoftDeletes
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Model
{
    /**
     * @var bool
     */
    private $isFromCache = false;

    /**
     * @param  DateTimeInterface|int|float|string|null  $date
     * @param  string|null  $format
     *
     * @return Carbon|null
     */
    protected function toDateTime($date = null, ?string $format = null): ?Carbon
    {
        $format = $format ?? $this->getDateFormat();

        return Carbon::fromDate($date, $format);
    }

    /**
     * @param  DateTimeInterface|int|float|null  $dateTime
     * @param  string  $format
     *
     * @return string|null
     */
    protected function formatDateTime($dateTime, string $format = 'Y-m-d H:i:s'): ?string
    {
        $dateTime = $this->toDateTime($dateTime);

        return $dateTime instanceof DateTimeInterface ? $dateTime->format($format) : null;
    }

    /**
     * @param  mixed  $date
     *
     * @return null|Carbon
     */
    public function getCreatedAtAttribute($date)
    {
        return $this->toDateTime($date);
    }

    /**
     * @param  mixed  $date
     *
     * @return null|Carbon
     */
    public function getUpdatedAtAttribute($date)
    {
        return $this->toDateTime($date);
    }

    /**
     * @param  mixed  $date
     *
     * @return null|Carbon
     */
    public function getDeletedAtAttribute($date)
    {
        return $this->toDateTime($date);
    }

    public function formatDateColumn($name, string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->formatDateTime($this->{$name}, $format);
    }

    public function formatCreatedAt(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->formatDateColumn($this->getCreatedAtColumn(), $format);
    }

    public function formatUpdatedAt(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->formatDateColumn($this->getUpdatedAtColumn(), $format);
    }

    public function formatDeleteAt(string $format = 'Y-m-d H:i:s'): ?string
    {
        return $this->formatDateColumn($this->getDeletedAtColumn(), $format);
    }

    public function getSetColumnCollection($name, $separator = ',', $filter = null): IlluminateCollection
    {
        $values = Arr::wrap(explode($separator, $this->{$name}));

        return IlluminateCollection::make($values)->filter($filter)->unique()->values();
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

    public function getDeletedAtColumn()
    {
        return defined('static::DELETED_AT') ? constant('static::DELETED_AT') : 'deleted_at';
    }

    public function genDefaultSort(): int
    {
        return Carbon::now()->getTimestamp() - 1660899108;
    }

    public static function genModelVersion(): int
    {
        return abs(crc32(serialize([Str::random(100), microtime()])));
    }

    /**
     * @param $query
     *
     * @return Builder
     */
    public function newEloquentBuilder($query): Builder
    {
        return new Builder($query);
    }

    public static function noCacheQuery(): Builder
    {
        return static::query()->noCache();
    }

    public static function availableQuery(): Builder
    {
        return static::query()->whereDeletedAtColumn();
    }

    public static function sortQuery(): Builder
    {
        return static::query()->orderByDesc('sort')->orderByDesc('id');
    }

    public static function sortAvailableQuery(): Builder
    {
        return static::availableQuery()->orderByDesc('sort')->orderByDesc('id');
    }

    public function getCache(): ?CacheInterface
    {
        return null;
    }

    public function getCachePlaceholder(): ?string
    {
        return null;
    }

    public function hasCachePlaceholder(): bool
    {
        return null !== $this->getCachePlaceholder();
    }

    public function isCachePlaceholder($value): bool
    {
        return $value === $this->getCachePlaceholder() && $this->hasCachePlaceholder();
    }

    public function getModelCachePrefix(): string
    {
        return 'm-v1';
    }

    public function getCacheVersion(): string
    {
        return 'v1';
    }

    /**
     * @throws
     *
     * @phpstan-ignore-next-line
     */
    public function getCacheTtl(int $duration = null): int
    {
        return null === $duration ? random_int(5 * 24 * 3600, 7 * 24 * 3600) : $duration;
    }

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

    public function onChangeRefreshCacheKeys(): array
    {
        return [
            [$this->getKeyName() => $this->getKey()],
        ];
    }

    /**
     * @deprecated static::deleteRowCache()
     */
    public function refreshRowCache(): bool
    {
        return $this->deleteRowCache();
    }

    /**
     * @throws
     *
     * @phpstan-ignore-next-line
     */
    public function deleteRowCache(): bool
    {
        $cacheKeys = Collection::make($this->onChangeRefreshCacheKeys())
            ->mapWithKeys(function ($id, $key) {
                return [$key => $this->makeColumnsCacheKey($id)];
            });

        return $this->newQuery()->getCache()->deleteMultiple($cacheKeys->values());
    }

    /**
     * @throws
     *
     * @phpstan-ignore-next-line
     */
    public function resetRowCache(): bool
    {
        $cacheKeys = Collection::make($this->onChangeRefreshCacheKeys())
            ->mapWithKeys(function ($id, $key) {
                return [$this->makeColumnsCacheKey($id) => $this];
            });

        return $this->newQuery()->getCache()->setMultiple($cacheKeys, $this->getCacheTtl());
    }

    /**
     * make字段的缓存key.
     */
    public function makeColumnsCacheKey(array $columns): string
    {
        $cacheKey = [];
        foreach ($columns as $name => $value) {
            $name = is_numeric($name) ? $this->getKeyName() : $name;
            $cacheKey[strval($name)] = strval($value);
        }

        ksort($cacheKey);
        $cacheKey = json_encode($cacheKey);

        $string = sprintf('%s:%s', get_class($this), $cacheKey);

        return sprintf(
            '%s:%s-%s:%s:%s-%s',
            $this->getModelCachePrefix(),
            Str::snake(Str::afterLast(get_class($this), '\\')),
            Base::conv(abs(crc32(get_class($this))), '0123456789', '0123456789abcdefghijklmnopqrstuvwxyz'),
            $this->getCacheVersion(),
            Base::conv(strtoupper(md5($string)), '0123456789abcdef', '0123456789abcdefghijklmnopqrstuvwxyz'),
            Base::conv(abs(crc32($string)), '0123456789', '0123456789abcdefghijklmnopqrstuvwxyz')
        );
    }

    /**
     * @param  mixed  $id
     *
     * @return null|static
     */
    public static function findById($id)
    {
        return static::findByIds([$id])->first();
    }

    /**
     * @param  array|Arrayable|Traversable  $ids
     *
     * @return KnightCollection<int, static>|array<int, static>
     */
    public static function findByIds($ids): KnightCollection
    {
        return static::query()->findByPks($ids);
    }

    /**
     * Is a primary key value.
     *
     * @param  mixed  $value
     *
     * @return bool
     */
    public static function isMatchPk($value): bool
    {
        return !empty($value);
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

    /**
     * @return null|$this
     *
     * @example $model->isAvailable() ? $model : null
     *          $model->ifReturnSelf($model->isAvailable())
     * @example $model instanceof Model && $model->isAvailable() ? $model : null
     *          $model?->ifReturnSelf($model?->isAvailable())
     */
    public function ifReturnSelf($condition)
    {
        return $condition ? $this : null;
    }

    /**
     * @return null|$this
     *
     * @example $model->isAvailable() ? $model : null
     *          $model->ifAvailableReturnSelf()
     * @example $model instanceof Model && $model->isAvailable() ? $model : null
     *          $model?->ifAvailableReturnSelf()
     */
    public function ifAvailableReturnSelf()
    {
        return $this->ifReturnSelf($this->isAvailable());
    }

    /**
     * @return static
     */
    public function clone(callable $callable = null)
    {
        $model = clone $this;

        if (is_callable($callable)) {
            $callable($model);
        }

        return $model;
    }

    /**
     * @inheritDoc
     *
     * @return KnightCollection
     */
    public function newCollection(array $models = []): KnightCollection
    {
        return new KnightCollection($models);
    }

    public static function isAvailableModel($model): bool
    {
        return $model instanceof self && $model->isAvailable();
    }

    protected function json2Array($value, $filter = false): array
    {
        $collection = Collection::make();

        if (empty($value)) {
            $collection = Collection::make();
        } elseif (is_string($value)) {
            $collection = Collection::make(Json::decodeArray($value));
        } elseif (is_array($value)) {
            $collection = Collection::make($value);
        }

        if (true === $filter) {
            $collection = $collection->filter();
        } elseif (false !== $filter) {
            $collection = $collection->filter($filter);
        }

        return $collection->all();
    }
}
