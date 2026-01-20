<?php

namespace Illuminate\Database\Schema;

use HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin;

/**
 * IDE helper stub for Blueprint mixins.
 *
 * @see BlueprintMixin
 */
class Blueprint
{
    /**
     * @see BlueprintMixin::knightColumnsReversed()
     */
    public function knightColumnsReversed(): Blueprint
    {
        return $this;
    }

    /**
     * @see BlueprintMixin::knightColumns()
     */
    public function knightColumns(): Blueprint
    {
        return $this;
    }

    /**
     * @see BlueprintMixin::knightGin()
     *
     * @param string|array $columns
     */
    public function knightGin($columns, ?string $indexName = null): Blueprint
    {
        return $this;
    }

    /**
     * @see BlueprintMixin::knightUniqueWhere()
     *
     * @param string|array $columns
     */
    public function knightUniqueWhere($columns, string $whereCondition, ?string $indexName = null): Blueprint
    {
        return $this;
    }

    /**
     * @see BlueprintMixin::knightIndexWhere()
     *
     * @param string|array $columns
     */
    public function knightIndexWhere($columns, string $whereCondition, ?string $indexName = null): Blueprint
    {
        return $this;
    }

    /**
     * @see BlueprintMixin::knightUniqueWhereNotDeleted()
     *
     * @param string|array $columns
     */
    public function knightUniqueWhereNotDeleted(
        $columns,
        ?string $indexName = null,
        string $deletedAtColumn = 'deleted_at'
    ): Blueprint {
        return $this;
    }

    /**
     * @see BlueprintMixin::knightIndexWhereNotDeleted()
     *
     * @param string|array $columns
     */
    public function knightIndexWhereNotDeleted(
        $columns,
        ?string $indexName = null,
        string $deletedAtColumn = 'deleted_at'
    ): Blueprint {
        return $this;
    }
}
