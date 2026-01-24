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
use Illuminate\Support\Fluent;

/**
 * Migration 专用的 PostgreSQL Schema Grammar 扩展 Mixin.
 *
 * 为 PostgreSQL Schema Grammar 提供额外的索引编译方法扩展。
 * 此 Mixin 专门用于 Migration 场景，在 Migration 基类中自动注册。
 * 所有方法使用 knight 前缀以避免与框架冲突。
 *
 * @mixin-target \Illuminate\Database\Schema\Grammars\PostgresGrammar
 */
class PostgresGrammarMixin
{
    /**
     * 编译 GIN 索引命令.
     *
     * 支持单列和多列 GIN 索引。
     * 注意: 多列联合索引需要先安装 btree_gin 扩展: CREATE EXTENSION btree_gin;
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightGinIndex(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            /** @var \Illuminate\Database\Schema\Grammars\PostgresGrammar $this */
            $tableName = $this->wrapTable($blueprint);
            $indexName = $command->indexName;
            $columns = $this->columnize($command->columns);

            return "CREATE INDEX {$indexName} ON {$tableName} USING GIN ({$columns})";
        };
    }

    /**
     * 编译条件唯一索引命令.
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightUniqueWhere(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            /** @var \Illuminate\Database\Schema\Grammars\PostgresGrammar $this */
            $tableName = $this->wrapTable($blueprint);
            $indexName = $command->indexName;
            $columns = $this->columnize($command->columns);
            $where = $command->where;

            return "CREATE UNIQUE INDEX {$indexName} ON {$tableName} ({$columns}) WHERE {$where}";
        };
    }

    /**
     * 编译条件索引命令.
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightIndexWhere(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            /** @var \Illuminate\Database\Schema\Grammars\PostgresGrammar $this */
            $tableName = $this->wrapTable($blueprint);
            $indexName = $command->indexName;
            $columns = $this->columnize($command->columns);
            $where = $command->where;

            return "CREATE INDEX {$indexName} ON {$tableName} ({$columns}) WHERE {$where}";
        };
    }

    /**
     * 编译条件 GIN 索引命令.
     *
     * 支持单列和多列条件 GIN 索引。
     * 注意: 多列联合索引需要先安装 btree_gin 扩展: CREATE EXTENSION btree_gin;
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightGinIndexWhere(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            /** @var \Illuminate\Database\Schema\Grammars\PostgresGrammar $this */
            $tableName = $this->wrapTable($blueprint);
            $indexName = $command->indexName;
            $columns = $this->columnize($command->columns);
            $where = $command->where;

            return "CREATE INDEX {$indexName} ON {$tableName} USING GIN ({$columns}) WHERE {$where}";
        };
    }
}
