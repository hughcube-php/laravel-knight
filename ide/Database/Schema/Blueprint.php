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
    public function knightColumnsReversed()
    {
    }

    /**
     * @see BlueprintMixin::knightColumns()
     */
    public function knightColumns()
    {
    }

    /**
     * @param string|array $columns
     * @see BlueprintMixin::knightGin()
     */
    public function knightGin($columns, ?string $indexName = null)
    {
    }

    /**
     * @param string|array $columns
     * @see BlueprintMixin::knightGinWhere()
     */
    public function knightGinWhere($columns, string $whereCondition, ?string $indexName = null)
    {
    }

    /**
     * @param string|array $columns
     * @see BlueprintMixin::knightGinWhereNotDeleted()
     */
    public function knightGinWhereNotDeleted($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at')
    {
    }

    /**
     * @param string|array $columns
     * @see BlueprintMixin::knightUniqueWhere()
     */
    public function knightUniqueWhere($columns, string $whereCondition, ?string $indexName = null)
    {
    }

    /**
     * @param string|array $columns
     * @see BlueprintMixin::knightIndexWhere()
     *
     */
    public function knightIndexWhere($columns, string $whereCondition, ?string $indexName = null)
    {
    }

    /**
     * @param string|array $columns
     * @see BlueprintMixin::knightUniqueWhereNotDeleted()
     */
    public function knightUniqueWhereNotDeleted($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at')
    {
    }

    /**
     * @param string|array $columns
     * @see BlueprintMixin::knightIndexWhereNotDeleted()
     */
    public function knightIndexWhereNotDeleted($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at')
    {
    }
}
