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

    // ==================== PostgreSQL Array Column Type Methods ====================

    /**
     * 定义 INTEGER[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightIntArray(): Closure
    {
        return function (Fluent $column) {
            return 'integer[]';
        };
    }

    /**
     * 定义 BIGINT[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightBigIntArray(): Closure
    {
        return function (Fluent $column) {
            return 'bigint[]';
        };
    }

    /**
     * 定义 SMALLINT[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightSmallIntArray(): Closure
    {
        return function (Fluent $column) {
            return 'smallint[]';
        };
    }

    /**
     * 定义 TEXT[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightTextArray(): Closure
    {
        return function (Fluent $column) {
            return 'text[]';
        };
    }

    /**
     * 定义 VARCHAR(n)[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightVarcharArray(): Closure
    {
        return function (Fluent $column) {
            $length = $column->length ?? 255;

            return "varchar({$length})[]";
        };
    }

    /**
     * 定义 BOOLEAN[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightBooleanArray(): Closure
    {
        return function (Fluent $column) {
            return 'boolean[]';
        };
    }

    /**
     * 定义 DOUBLE PRECISION[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightDoubleArray(): Closure
    {
        return function (Fluent $column) {
            return 'double precision[]';
        };
    }

    /**
     * 定义 REAL[] (单精度浮点) 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightFloatArray(): Closure
    {
        return function (Fluent $column) {
            return 'real[]';
        };
    }

    /**
     * 定义 UUID[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightUuidArray(): Closure
    {
        return function (Fluent $column) {
            return 'uuid[]';
        };
    }

    /**
     * 定义 NUMERIC[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightNumericArray(): Closure
    {
        return function (Fluent $column) {
            $precision = $column->precision;
            $scale = $column->scale;

            if ($precision !== null && $scale !== null) {
                return "numeric({$precision}, {$scale})[]";
            }

            if ($precision !== null) {
                return "numeric({$precision})[]";
            }

            return 'numeric[]';
        };
    }

    /**
     * 定义 TIMESTAMPTZ[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightTimestamptzArray(): Closure
    {
        return function (Fluent $column) {
            return 'timestamptz[]';
        };
    }

    /**
     * 定义 DATE[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightDateArray(): Closure
    {
        return function (Fluent $column) {
            return 'date[]';
        };
    }

    /**
     * 定义 JSONB[] 数组列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightJsonbArray(): Closure
    {
        return function (Fluent $column) {
            return 'jsonb[]';
        };
    }

    // ==================== PostgreSQL Full-Text Search Column Type Methods ====================

    /**
     * 定义 TSVECTOR 列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightTsVector(): Closure
    {
        return function (Fluent $column) {
            return 'tsvector';
        };
    }

    /**
     * 定义 TSQUERY 列类型.
     *
     * @return Closure(Fluent): string
     */
    public function typeKnightTsQuery(): Closure
    {
        return function (Fluent $column) {
            return 'tsquery';
        };
    }

    // ==================== PostgreSQL Sequence Methods ====================

    /**
     * 编译设置序列值命令.
     *
     * 使用 setval() 函数设置序列的下一个值。
     * 第三个参数 false 表示下一次 nextval() 将返回指定的值。
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightSetSequenceValue(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            $sequenceName = $command->sequenceName;
            $value = $command->value;

            return "SELECT setval('{$sequenceName}', {$value}, false)";
        };
    }

    /**
     * 编译重启序列命令.
     *
     * 使用 ALTER SEQUENCE ... RESTART WITH 语法重启序列。
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightRestartSequence(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            $sequenceName = $command->sequenceName;
            $value = $command->value;

            return "ALTER SEQUENCE \"{$sequenceName}\" RESTART WITH {$value}";
        };
    }

    /**
     * 编译创建序列命令.
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightCreateSequence(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            $sequenceName = $command->sequenceName;
            $startWith = $command->startWith;
            $incrementBy = $command->incrementBy;
            $maxValue = $command->maxValue;
            $minValue = $command->minValue;
            $cycle = $command->cycle;
            $cache = $command->cache ?? 1;

            $sql = "CREATE SEQUENCE \"{$sequenceName}\"";
            $sql .= " START WITH {$startWith}";
            $sql .= " INCREMENT BY {$incrementBy}";
            $sql .= " MINVALUE {$minValue}";

            if ($maxValue !== null) {
                $sql .= " MAXVALUE {$maxValue}";
            } else {
                $sql .= " NO MAXVALUE";
            }

            $sql .= $cycle ? " CYCLE" : " NO CYCLE";
            $sql .= " CACHE {$cache}";

            return $sql;
        };
    }

    /**
     * 编译删除序列命令.
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightDropSequence(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            $sequenceName = $command->sequenceName;
            $ifExists = $command->ifExists;

            $sql = "DROP SEQUENCE";
            if ($ifExists) {
                $sql .= " IF EXISTS";
            }
            $sql .= " \"{$sequenceName}\"";

            return $sql;
        };
    }

    /**
     * 编译使用序列的主键列命令.
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightIdWithSequence(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            /** @var \Illuminate\Database\Schema\Grammars\PostgresGrammar $this */
            $tableName = $this->wrapTable($blueprint);
            $column = $command->column;
            $sequenceName = $command->sequenceName;
            $primary = $command->primary;

            $sql = "ALTER TABLE {$tableName} ADD COLUMN \"{$column}\" BIGINT";
            $sql .= " DEFAULT nextval('{$sequenceName}') NOT NULL";

            if ($primary) {
                $sql .= "; ALTER TABLE {$tableName} ADD PRIMARY KEY (\"{$column}\")";
            }

            return $sql;
        };
    }

    /**
     * 编译修改列使用序列命令.
     *
     * @return Closure(Blueprint, Fluent): string
     */
    public function compileKnightUseSequence(): Closure
    {
        return function (Blueprint $blueprint, Fluent $command) {
            /** @var \Illuminate\Database\Schema\Grammars\PostgresGrammar $this */
            $tableName = $this->wrapTable($blueprint);
            $column = $command->column;
            $sequenceName = $command->sequenceName;

            return "ALTER TABLE {$tableName} ALTER COLUMN \"{$column}\" SET DEFAULT nextval('{$sequenceName}')";
        };
    }
}
