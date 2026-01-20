<?php

namespace Illuminate\Support;

use HughCube\Laravel\Knight\Mixin\Support\CollectionMixin;

/**
 * IDE helper stub for Collection mixins.
 *
 * @see CollectionMixin
 */
class Collection
{
    /**
     * @see CollectionMixin::hasByCallable()
     */
    public function hasByCallable(callable $key): bool
    {
        return false;
    }

    /**
     * @see CollectionMixin::hasAnyValues()
     */
    public function hasAnyValues($values, bool $strict = false): bool
    {
        return false;
    }

    /**
     * @see CollectionMixin::hasAllValues()
     */
    public function hasAllValues($values, bool $strict = false): bool
    {
        return false;
    }

    /**
     * @see CollectionMixin::hasValue()
     */
    public function hasValue($needle, $strict = false): bool
    {
        return false;
    }

    /**
     * @see CollectionMixin::isIndexed()
     */
    public function isIndexed(bool $consecutive = true): bool
    {
        return false;
    }

    /**
     * @see CollectionMixin::afterFirstItems()
     */
    public function afterFirstItems($value = null, $withBeacon = false, $strict = false): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::afterLastItems()
     */
    public function afterLastItems($value = null, $withBeacon = false, $strict = false): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::filterWithStop()
     */
    public function filterWithStop(callable $stop, $withStopItem = false): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::pluckAndMergeSetColumn()
     */
    public function pluckAndMergeSetColumn($name, $separator = ',', $filter = null): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::pluckAndMergeArrayColumn()
     */
    public function pluckAndMergeArrayColumn($name): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::onlyArrayKeys()
     */
    public function onlyArrayKeys($keys = []): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::onlyColumnValues()
     */
    public function onlyColumnValues($values, $name = null, bool $strict = false): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::whenFilter()
     */
    public function whenFilter($when, callable $callable): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::mapInt()
     */
    public function mapInt(): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::mapString()
     */
    public function mapString(): Collection
    {
        return $this;
    }

    /**
     * @see CollectionMixin::explode()
     */
    public static function explode(string $separator, string $string, int $limit = PHP_INT_MAX): Collection
    {
        return new Collection();
    }

    /**
     * @see CollectionMixin::splitWhitespace()
     */
    public static function splitWhitespace(string $separator, string $pattern = '/\\s+/', int $limit = -1): Collection
    {
        return new Collection();
    }

    /**
     * @see CollectionMixin::splitComma()
     */
    public static function splitComma(string $separator, string $pattern = '/[,，]/', int $limit = -1): Collection
    {
        return new Collection();
    }

    /**
     * @see CollectionMixin::splitSlash()
     */
    public static function splitSlash(string $separator, string $pattern = '#[\\/／]#', int $limit = -1): Collection
    {
        return new Collection();
    }
}
