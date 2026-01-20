<?php

namespace Illuminate\Database\Query;

use HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin;

/**
 * IDE helper stub for Builder mixins.
 *
 * @see BuilderMixin
 */
class Builder
{
    /**
     * @see BuilderMixin::whereArrayContains()
     */
    public function whereArrayContains($column, $value, $boolean = 'and', $not = false, $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::orWhereArrayContains()
     */
    public function orWhereArrayContains($column, $value, $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::whereNotArrayContains()
     */
    public function whereNotArrayContains($column, $value, $boolean = 'and', $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::orWhereNotArrayContains()
     */
    public function orWhereNotArrayContains($column, $value, $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::whereArrayContainedBy()
     */
    public function whereArrayContainedBy($column, $value, $boolean = 'and', $not = false, $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::orWhereArrayContainedBy()
     */
    public function orWhereArrayContainedBy($column, $value, $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::whereNotArrayContainedBy()
     */
    public function whereNotArrayContainedBy($column, $value, $boolean = 'and', $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::orWhereNotArrayContainedBy()
     */
    public function orWhereNotArrayContainedBy($column, $value, $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::whereArrayOverlaps()
     */
    public function whereArrayOverlaps($column, $value, $boolean = 'and', $not = false, $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::orWhereArrayOverlaps()
     */
    public function orWhereArrayOverlaps($column, $value, $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::whereNotArrayOverlaps()
     */
    public function whereNotArrayOverlaps($column, $value, $boolean = 'and', $arrayType = null): Builder
    {
        return $this;
    }

    /**
     * @see BuilderMixin::orWhereNotArrayOverlaps()
     */
    public function orWhereNotArrayOverlaps($column, $value, $arrayType = null): Builder
    {
        return $this;
    }
}
