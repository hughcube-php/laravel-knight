<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2026/1/20
 * Time: 10:00.
 */

namespace HughCube\Laravel\Knight\Database\Migrations\Mixin;

use Closure;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration 专用的 Blueprint 扩展 Mixin.
 *
 * 为 Laravel Schema Blueprint 提供额外的字段和索引方法扩展。
 * 此 Mixin 专门用于 Migration 场景，在 Migration 基类中自动注册。
 * 所有方法使用 knight 前缀以避免与框架冲突。
 *
 * @mixin-target \Illuminate\Database\Schema\Blueprint
 */
class BlueprintMixin
{
    /**
     * 添加 Knight 常用字段(反顺序).
     *
     * 字段顺序: created_at, updated_at, deleted_at, ukey, data_version
     *
     * 示例:
     *   Schema::create('users', function (Blueprint $table) {
     *       $table->id();
     *       $table->string('name');
     *       $table->knightColumnsReversed();
     *   });
     *
     * @return Closure
     */
    public function knightColumnsReversed(): Closure
    {
        return function () {
            /** @var Blueprint $this */

            $this->timestamp('created_at')->nullable()->default(null)->comment('Record creation timestamp');
            $this->timestamp('updated_at')->nullable()->default(null)->comment('Record last update timestamp');
            $this->timestamp('deleted_at')->nullable()->default(null)->comment('Soft delete timestamp');
            $this->bigInteger('data_version')->default(0)->comment('Data version for optimistic locking');
            $this->string('ukey', 255)->nullable()->default('')->comment('Unique key for composite unique index');
            $this->jsonb('options')->default('{}')->comment('Extra options');
            $this->bigInteger('sort')->default(0)->comment('Sorting weight');

            return $this;
        };
    }

    /**
     * 添加 Knight 常用字段.
     *
     * 字段顺序: ukey, data_version, created_at, updated_at, deleted_at
     *
     * 示例:
     *   Schema::create('users', function (Blueprint $table) {
     *       $table->id();
     *       $table->string('name');
     *       $table->knightColumns();
     *   });
     *
     * @return Closure
     */
    public function knightColumns(): Closure
    {
        return function () {
            /** @var Blueprint $this */

            $this->bigInteger('sort')->default(0)->comment('Sorting weight');
            $this->jsonb('options')->default('{}')->comment('Extra options');
            $this->string('ukey', 255)->nullable()->default('')->comment('Unique key for composite unique index');
            $this->bigInteger('data_version')->default(0)->comment('Data version for optimistic locking');
            $this->timestamp('created_at')->nullable()->default(null)->comment('Record creation timestamp');
            $this->timestamp('updated_at')->nullable()->default(null)->comment('Record last update timestamp');
            $this->timestamp('deleted_at')->nullable()->default(null)->comment('Soft delete timestamp');

            return $this;
        };
    }

    /**
     * 创建 GIN 索引 (PostgreSQL).
     *
     * 注意: 多列联合索引需要先安装 btree_gin 扩展: CREATE EXTENSION btree_gin;
     *
     * 示例:
     *   $table->knightGin('tags');
     *   $table->knightGin(['tenant_id', 'tags']);
     *   $table->knightGin('metadata', 'custom_index_name');
     *
     * @return Closure(string|array $columns, string|null $indexName = null): static
     */
    public function knightGin(): Closure
    {
        return function ($columns, ?string $indexName = null) {
            /** @var Blueprint $this */
            $tableName = $this->getTable();
            $columns = is_array($columns) ? $columns : [$columns];
            $columnSuffix = implode('_', $columns);
            $indexName = $indexName ?? "idx_{$tableName}_{$columnSuffix}_gin";

            $this->addCommand('knightGinIndex', ['columns' => $columns, 'indexName' => $indexName]);

            return $this;
        };
    }

    /**
     * 创建条件 GIN 索引 (PostgreSQL).
     *
     * 注意: 多列联合索引需要先安装 btree_gin 扩展: CREATE EXTENSION btree_gin;
     *
     * 示例:
     *   $table->knightGinWhere('tags', 'deleted_at IS NULL');
     *   $table->knightGinWhere(['tenant_id', 'tags'], 'deleted_at IS NULL');
     *
     * @return Closure(string|array $columns, string $whereCondition, string|null $indexName = null): static
     */
    public function knightGinWhere(): Closure
    {
        return function ($columns, string $whereCondition, ?string $indexName = null) {
            /** @var Blueprint $this */
            $tableName = $this->getTable();
            $columns = is_array($columns) ? $columns : [$columns];
            $columnSuffix = implode('_', $columns);
            $indexName = $indexName ?? "idx_{$tableName}_{$columnSuffix}_gin";

            $this->addCommand('knightGinIndexWhere', [
                'columns' => $columns,
                'where' => $whereCondition,
                'indexName' => $indexName,
            ]);

            return $this;
        };
    }

    /**
     * 创建条件 GIN 索引，仅对未软删除的记录生效 (PostgreSQL).
     *
     * 注意: 多列联合索引需要先安装 btree_gin 扩展: CREATE EXTENSION btree_gin;
     *
     * 示例:
     *   $table->knightGinWhereNotDeleted('tags');
     *   $table->knightGinWhereNotDeleted(['tenant_id', 'tags']);
     *
     * @return Closure
     */
    public function knightGinWhereNotDeleted(): Closure
    {
        return function ($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at') {
            /** @var Blueprint $this */
            /** @phpstan-ignore-next-line */
            return $this->knightGinWhere($columns, "{$deletedAtColumn} IS NULL", $indexName);
        };
    }

    /**
     * 创建条件唯一索引 (PostgreSQL).
     *
     * 示例:
     *   $table->knightUniqueWhere('email', 'deleted_at IS NULL');
     *   $table->knightUniqueWhere(['tenant_id', 'email'], 'deleted_at IS NULL');
     *
     * @return Closure(string|array $columns, string $whereCondition, string|null $indexName = null): static
     */
    public function knightUniqueWhere(): Closure
    {
        return function ($columns, string $whereCondition, ?string $indexName = null) {
            /** @var Blueprint $this */
            $tableName = $this->getTable();
            $columns = is_array($columns) ? $columns : [$columns];
            $columnSuffix = implode('_', $columns);
            $indexName = $indexName ?? "uk_{$tableName}_{$columnSuffix}";

            $this->addCommand('knightUniqueWhere', [
                'columns' => $columns,
                'where' => $whereCondition,
                'indexName' => $indexName,
            ]);

            return $this;
        };
    }

    /**
     * 创建条件索引 (PostgreSQL).
     *
     * 示例:
     *   $table->knightIndexWhere('status', "status = 'active'");
     *   $table->knightIndexWhere(['user_id', 'status'], 'deleted_at IS NULL');
     *
     * @return Closure
     */
    public function knightIndexWhere(): Closure
    {
        return function ($columns, string $whereCondition, ?string $indexName = null) {
            /** @var Blueprint $this */
            $tableName = $this->getTable();
            $columns = is_array($columns) ? $columns : [$columns];
            $columnSuffix = implode('_', $columns);
            $indexName = $indexName ?? "idx_{$tableName}_{$columnSuffix}";

            $this->addCommand('knightIndexWhere', [
                'columns' => $columns,
                'where' => $whereCondition,
                'indexName' => $indexName,
            ]);

            return $this;
        };
    }

    /**
     * 创建条件唯一索引，仅对未软删除的记录生效 (PostgreSQL).
     *
     * 示例:
     *   $table->knightUniqueWhereNotDeleted('email');
     *   $table->knightUniqueWhereNotDeleted(['tenant_id', 'email']);
     *
     * @return Closure
     */
    public function knightUniqueWhereNotDeleted(): Closure
    {
        return function ($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at') {
            /** @var Blueprint $this */
            /** @phpstan-ignore-next-line */
            return $this->knightUniqueWhere($columns, "{$deletedAtColumn} IS NULL", $indexName);
        };
    }

    /**
     * 创建条件索引，仅对未软删除的记录生效 (PostgreSQL).
     *
     * 示例:
     *   $table->knightIndexWhereNotDeleted('user_id');
     *   $table->knightIndexWhereNotDeleted(['user_id', 'status']);
     *
     * @return Closure
     */
    public function knightIndexWhereNotDeleted(): Closure
    {
        return function ($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at') {
            /** @var Blueprint $this */
            /** @phpstan-ignore-next-line */
            return $this->knightIndexWhere($columns, "{$deletedAtColumn} IS NULL", $indexName);
        };
    }

    // ==================== PostgreSQL Array Column Methods ====================

    /**
     * 添加 INTEGER[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightIntArray('scores');
     *   $table->knightIntArray('scores')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightIntArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightIntArray', $column);
        };
    }

    /**
     * 添加 BIGINT[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightBigIntArray('user_ids');
     *   $table->knightBigIntArray('user_ids')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightBigIntArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightBigIntArray', $column);
        };
    }

    /**
     * 添加 SMALLINT[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightSmallIntArray('ratings');
     *   $table->knightSmallIntArray('ratings')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightSmallIntArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightSmallIntArray', $column);
        };
    }

    /**
     * 添加 TEXT[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightTextArray('tags');
     *   $table->knightTextArray('tags')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightTextArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightTextArray', $column);
        };
    }

    /**
     * 添加 VARCHAR(n)[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightVarcharArray('codes', 50);
     *   $table->knightVarcharArray('codes', 50)->nullable();
     *
     * @return Closure(string $column, int $length = 255): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightVarcharArray(): Closure
    {
        return function (string $column, int $length = 255) {
            /** @var Blueprint $this */
            return $this->addColumn('knightVarcharArray', $column, ['length' => $length]);
        };
    }

    /**
     * 添加 BOOLEAN[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightBooleanArray('flags');
     *   $table->knightBooleanArray('flags')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightBooleanArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightBooleanArray', $column);
        };
    }

    /**
     * 添加 DOUBLE PRECISION[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightDoubleArray('prices');
     *   $table->knightDoubleArray('prices')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightDoubleArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightDoubleArray', $column);
        };
    }

    /**
     * 添加 REAL[] (单精度浮点) 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightFloatArray('coordinates');
     *   $table->knightFloatArray('coordinates')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightFloatArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightFloatArray', $column);
        };
    }

    /**
     * 添加 UUID[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightUuidArray('related_ids');
     *   $table->knightUuidArray('related_ids')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightUuidArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightUuidArray', $column);
        };
    }

    /**
     * 添加 NUMERIC[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightNumericArray('amounts');
     *   $table->knightNumericArray('amounts', 10, 2)->nullable();
     *
     * @return Closure(string $column, int|null $precision = null, int|null $scale = null): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightNumericArray(): Closure
    {
        return function (string $column, ?int $precision = null, ?int $scale = null) {
            /** @var Blueprint $this */
            return $this->addColumn('knightNumericArray', $column, [
                'precision' => $precision,
                'scale' => $scale,
            ]);
        };
    }

    /**
     * 添加 TIMESTAMPTZ[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightTimestamptzArray('event_times');
     *   $table->knightTimestamptzArray('event_times')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightTimestamptzArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightTimestamptzArray', $column);
        };
    }

    /**
     * 添加 DATE[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightDateArray('holidays');
     *   $table->knightDateArray('holidays')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightDateArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightDateArray', $column);
        };
    }

    /**
     * 添加 JSONB[] 数组列 (PostgreSQL).
     *
     * 示例:
     *   $table->knightJsonbArray('metadata_list');
     *   $table->knightJsonbArray('metadata_list')->nullable();
     *
     * @return Closure(string $column): \Illuminate\Database\Schema\ColumnDefinition
     */
    public function knightJsonbArray(): Closure
    {
        return function (string $column) {
            /** @var Blueprint $this */
            return $this->addColumn('knightJsonbArray', $column);
        };
    }
}
