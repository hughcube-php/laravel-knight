<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2026/1/19
 * Time: 19:08
 */

namespace HughCube\Laravel\Knight\Database\Migrations;


use Illuminate\Database\Connection;
use Illuminate\Database\Migrations\Migration as IlluminateMigration;
use Illuminate\Support\Facades\DB;

/**
 * 迁移文件基类
 *
 * @property string $table 表名
 */
class Migration extends IlluminateMigration
{
    /**
     * 表名
     */
    protected string $table;

    /**
     * 获取数据库连接实例
     */
    protected function getDB(): Connection
    {
        return DB::connection($this->getConnection());
    }

    /**
     * 创建 GIN 索引 (PostgreSQL)
     *
     * @param string $column 列名
     * @param string|null $indexName 索引名称，默认自动生成
     */
    protected function createGinIndex(string $column, ?string $indexName = null): void
    {
        $indexName = $indexName ?? "idx_{$this->table}_{$column}_gin";

        $this->getDB()->statement(
            "CREATE INDEX {$indexName} ON {$this->table} USING GIN ({$column})"
        );
    }

    /**
     * 创建条件唯一索引 (PostgreSQL)
     *
     * @param string|array $columns 列名或列名数组
     * @param string $whereCondition WHERE 条件
     * @param string|null $indexName 索引名称，默认自动生成
     */
    protected function createUniqueIndexWhere($columns, string $whereCondition, ?string $indexName = null): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $columnStr = implode(', ', $columns);
        $columnSuffix = implode('_', $columns);
        $indexName = $indexName ?? "uk_{$this->table}_{$columnSuffix}";

        $this->getDB()->statement(
            "CREATE UNIQUE INDEX {$indexName} ON {$this->table} ({$columnStr}) WHERE {$whereCondition}"
        );
    }

    /**
     * 创建条件索引 (PostgreSQL)
     *
     * @param string|array $columns 列名或列名数组
     * @param string $whereCondition WHERE 条件
     * @param string|null $indexName 索引名称，默认自动生成
     */
    protected function createIndexWhere($columns, string $whereCondition, ?string $indexName = null): void
    {
        $columns = is_array($columns) ? $columns : [$columns];
        $columnStr = implode(', ', $columns);
        $columnSuffix = implode('_', $columns);
        $indexName = $indexName ?? "idx_{$this->table}_{$columnSuffix}";

        $this->getDB()->statement(
            "CREATE INDEX {$indexName} ON {$this->table} ({$columnStr}) WHERE {$whereCondition}"
        );
    }

    /**
     * 创建条件唯一索引，仅对未软删除的记录生效 (PostgreSQL)
     *
     * @param string|array $columns 列名或列名数组
     * @param string|null $indexName 索引名称，默认自动生成
     * @param string $deletedAtColumn 软删除列名，默认 deleted_at
     */
    protected function createUniqueIndexWhereNotDeleted($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at'): void
    {
        $this->createUniqueIndexWhere($columns, "{$deletedAtColumn} IS NULL", $indexName);
    }

    /**
     * 创建条件索引，仅对未软删除的记录生效 (PostgreSQL)
     *
     * @param string|array $columns 列名或列名数组
     * @param string|null $indexName 索引名称，默认自动生成
     * @param string $deletedAtColumn 软删除列名，默认 deleted_at
     */
    protected function createIndexWhereNotDeleted($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at'): void
    {
        $this->createIndexWhere($columns, "{$deletedAtColumn} IS NULL", $indexName);
    }
}
