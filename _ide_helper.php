<?php
/* @noinspection ALL */
// @formatter:off
// phpcs:ignoreFile

/**
 * IDE helper for App Mixin methods.
 *
 * 此文件由 ide:generate-mixin-helper 命令自动生成，请勿手动修改。
 */

namespace Illuminate\Database\Schema {

    /**
     * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin
     */
    class Blueprint
    {
        /**
         * 添加 Knight 常用字段(反顺序).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightColumnsReversed()
         */
        public function knightColumnsReversed(): mixed
        {
        }

        /**
         * 添加 Knight 常用字段.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightColumns()
         */
        public function knightColumns(): mixed
        {
        }

        /**
         * 创建 GIN 索引 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightGin()
         */
        public function knightGin($columns, ?string $indexName = null): mixed
        {
        }

        /**
         * 创建条件 GIN 索引 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightGinWhere()
         */
        public function knightGinWhere($columns, string $whereCondition, ?string $indexName = null): mixed
        {
        }

        /**
         * 创建条件 GIN 索引，仅对未软删除的记录生效 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightGinWhereNotDeleted()
         */
        public function knightGinWhereNotDeleted($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at'): mixed
        {
        }

        /**
         * 创建条件唯一索引 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightUniqueWhere()
         */
        public function knightUniqueWhere($columns, string $whereCondition, ?string $indexName = null): mixed
        {
        }

        /**
         * 创建条件索引 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightIndexWhere()
         */
        public function knightIndexWhere($columns, string $whereCondition, ?string $indexName = null): mixed
        {
        }

        /**
         * 创建条件唯一索引，仅对未软删除的记录生效 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightUniqueWhereNotDeleted()
         */
        public function knightUniqueWhereNotDeleted($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at'): mixed
        {
        }

        /**
         * 创建条件索引，仅对未软删除的记录生效 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightIndexWhereNotDeleted()
         */
        public function knightIndexWhereNotDeleted($columns, ?string $indexName = null, string $deletedAtColumn = 'deleted_at'): mixed
        {
        }

        /**
         * 添加 INTEGER[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightIntArray()
         */
        public function knightIntArray(string $column): mixed
        {
        }

        /**
         * 添加 BIGINT[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightBigIntArray()
         */
        public function knightBigIntArray(string $column): mixed
        {
        }

        /**
         * 添加 SMALLINT[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightSmallIntArray()
         */
        public function knightSmallIntArray(string $column): mixed
        {
        }

        /**
         * 添加 TEXT[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightTextArray()
         */
        public function knightTextArray(string $column): mixed
        {
        }

        /**
         * 添加 VARCHAR(n)[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightVarcharArray()
         */
        public function knightVarcharArray(string $column, int $length = 255): mixed
        {
        }

        /**
         * 添加 BOOLEAN[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightBooleanArray()
         */
        public function knightBooleanArray(string $column): mixed
        {
        }

        /**
         * 添加 DOUBLE PRECISION[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightDoubleArray()
         */
        public function knightDoubleArray(string $column): mixed
        {
        }

        /**
         * 添加 REAL[] (单精度浮点) 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightFloatArray()
         */
        public function knightFloatArray(string $column): mixed
        {
        }

        /**
         * 添加 UUID[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightUuidArray()
         */
        public function knightUuidArray(string $column): mixed
        {
        }

        /**
         * 添加 NUMERIC[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightNumericArray()
         */
        public function knightNumericArray(string $column, ?int $precision = null, ?int $scale = null): mixed
        {
        }

        /**
         * 添加 TIMESTAMPTZ[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightTimestamptzArray()
         */
        public function knightTimestamptzArray(string $column): mixed
        {
        }

        /**
         * 添加 DATE[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightDateArray()
         */
        public function knightDateArray(string $column): mixed
        {
        }

        /**
         * 添加 JSONB[] 数组列 (PostgreSQL).
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\BlueprintMixin::knightJsonbArray()
         */
        public function knightJsonbArray(string $column): mixed
        {
        }
    }
}

namespace Illuminate\Database\Schema\Grammars {

    /**
     * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin
     */
    class PostgresGrammar
    {
        /**
         * 编译 GIN 索引命令.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::compileKnightGinIndex()
         */
        public function compileKnightGinIndex(\Illuminate\Database\Schema\Blueprint $blueprint, \Illuminate\Support\Fluent $command): mixed
        {
        }

        /**
         * 编译条件唯一索引命令.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::compileKnightUniqueWhere()
         */
        public function compileKnightUniqueWhere(\Illuminate\Database\Schema\Blueprint $blueprint, \Illuminate\Support\Fluent $command): mixed
        {
        }

        /**
         * 编译条件索引命令.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::compileKnightIndexWhere()
         */
        public function compileKnightIndexWhere(\Illuminate\Database\Schema\Blueprint $blueprint, \Illuminate\Support\Fluent $command): mixed
        {
        }

        /**
         * 编译条件 GIN 索引命令.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::compileKnightGinIndexWhere()
         */
        public function compileKnightGinIndexWhere(\Illuminate\Database\Schema\Blueprint $blueprint, \Illuminate\Support\Fluent $command): mixed
        {
        }

        /**
         * 定义 INTEGER[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightIntArray()
         */
        public function typeKnightIntArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 BIGINT[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightBigIntArray()
         */
        public function typeKnightBigIntArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 SMALLINT[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightSmallIntArray()
         */
        public function typeKnightSmallIntArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 TEXT[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightTextArray()
         */
        public function typeKnightTextArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 VARCHAR(n)[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightVarcharArray()
         */
        public function typeKnightVarcharArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 BOOLEAN[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightBooleanArray()
         */
        public function typeKnightBooleanArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 DOUBLE PRECISION[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightDoubleArray()
         */
        public function typeKnightDoubleArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 REAL[] (单精度浮点) 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightFloatArray()
         */
        public function typeKnightFloatArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 UUID[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightUuidArray()
         */
        public function typeKnightUuidArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 NUMERIC[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightNumericArray()
         */
        public function typeKnightNumericArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 TIMESTAMPTZ[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightTimestamptzArray()
         */
        public function typeKnightTimestamptzArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 DATE[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightDateArray()
         */
        public function typeKnightDateArray(\Illuminate\Support\Fluent $column): mixed
        {
        }

        /**
         * 定义 JSONB[] 数组列类型.
         * @see \HughCube\Laravel\Knight\Database\Migrations\Mixin\PostgresGrammarMixin::typeKnightJsonbArray()
         */
        public function typeKnightJsonbArray(\Illuminate\Support\Fluent $column): mixed
        {
        }
    }
}

namespace Illuminate\Database\Query {

    /**
     * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin
     */
    class Builder
    {
        /**
         * 添加 PostgreSQL INTEGER[] 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereIntArrayContains()
         */
        public function whereIntArrayContains($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 OR 条件的 INTEGER[] 数组包含查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereIntArrayContains()
         */
        public function orWhereIntArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 NOT 条件的 INTEGER[] 数组包含查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotIntArrayContains()
         */
        public function whereNotIntArrayContains($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 OR NOT 条件的 INTEGER[] 数组包含查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotIntArrayContains()
         */
        public function orWhereNotIntArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL INTEGER[] 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereIntArrayContainedBy()
         */
        public function whereIntArrayContainedBy($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 OR 条件的 INTEGER[] 数组被包含查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereIntArrayContainedBy()
         */
        public function orWhereIntArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 NOT 条件的 INTEGER[] 数组被包含查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotIntArrayContainedBy()
         */
        public function whereNotIntArrayContainedBy($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 OR NOT 条件的 INTEGER[] 数组被包含查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotIntArrayContainedBy()
         */
        public function orWhereNotIntArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL INTEGER[] 数组交集查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereIntArrayOverlaps()
         */
        public function whereIntArrayOverlaps($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 OR 条件的 INTEGER[] 数组交集查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereIntArrayOverlaps()
         */
        public function orWhereIntArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 NOT 条件的 INTEGER[] 数组交集查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotIntArrayOverlaps()
         */
        public function whereNotIntArrayOverlaps($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 OR NOT 条件的 INTEGER[] 数组交集查询.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotIntArrayOverlaps()
         */
        public function orWhereNotIntArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL BIGINT[] 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereBigIntArrayContains()
         */
        public function whereBigIntArrayContains($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereBigIntArrayContains()
         */
        public function orWhereBigIntArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotBigIntArrayContains()
         */
        public function whereNotBigIntArrayContains($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotBigIntArrayContains()
         */
        public function orWhereNotBigIntArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL BIGINT[] 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereBigIntArrayContainedBy()
         */
        public function whereBigIntArrayContainedBy($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereBigIntArrayContainedBy()
         */
        public function orWhereBigIntArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotBigIntArrayContainedBy()
         */
        public function whereNotBigIntArrayContainedBy($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotBigIntArrayContainedBy()
         */
        public function orWhereNotBigIntArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL BIGINT[] 数组交集查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereBigIntArrayOverlaps()
         */
        public function whereBigIntArrayOverlaps($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereBigIntArrayOverlaps()
         */
        public function orWhereBigIntArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotBigIntArrayOverlaps()
         */
        public function whereNotBigIntArrayOverlaps($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotBigIntArrayOverlaps()
         */
        public function orWhereNotBigIntArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL SMALLINT[] 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereSmallIntArrayContains()
         */
        public function whereSmallIntArrayContains($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereSmallIntArrayContains()
         */
        public function orWhereSmallIntArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotSmallIntArrayContains()
         */
        public function whereNotSmallIntArrayContains($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotSmallIntArrayContains()
         */
        public function orWhereNotSmallIntArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL SMALLINT[] 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereSmallIntArrayContainedBy()
         */
        public function whereSmallIntArrayContainedBy($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereSmallIntArrayContainedBy()
         */
        public function orWhereSmallIntArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotSmallIntArrayContainedBy()
         */
        public function whereNotSmallIntArrayContainedBy($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotSmallIntArrayContainedBy()
         */
        public function orWhereNotSmallIntArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL SMALLINT[] 数组交集查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereSmallIntArrayOverlaps()
         */
        public function whereSmallIntArrayOverlaps($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereSmallIntArrayOverlaps()
         */
        public function orWhereSmallIntArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotSmallIntArrayOverlaps()
         */
        public function whereNotSmallIntArrayOverlaps($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotSmallIntArrayOverlaps()
         */
        public function orWhereNotSmallIntArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL TEXT[] 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereTextArrayContains()
         */
        public function whereTextArrayContains($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereTextArrayContains()
         */
        public function orWhereTextArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotTextArrayContains()
         */
        public function whereNotTextArrayContains($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotTextArrayContains()
         */
        public function orWhereNotTextArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL TEXT[] 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereTextArrayContainedBy()
         */
        public function whereTextArrayContainedBy($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereTextArrayContainedBy()
         */
        public function orWhereTextArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotTextArrayContainedBy()
         */
        public function whereNotTextArrayContainedBy($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotTextArrayContainedBy()
         */
        public function orWhereNotTextArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL TEXT[] 数组交集查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereTextArrayOverlaps()
         */
        public function whereTextArrayOverlaps($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereTextArrayOverlaps()
         */
        public function orWhereTextArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotTextArrayOverlaps()
         */
        public function whereNotTextArrayOverlaps($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotTextArrayOverlaps()
         */
        public function orWhereNotTextArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL BOOLEAN[] 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereBooleanArrayContains()
         */
        public function whereBooleanArrayContains($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereBooleanArrayContains()
         */
        public function orWhereBooleanArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotBooleanArrayContains()
         */
        public function whereNotBooleanArrayContains($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotBooleanArrayContains()
         */
        public function orWhereNotBooleanArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL BOOLEAN[] 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereBooleanArrayContainedBy()
         */
        public function whereBooleanArrayContainedBy($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereBooleanArrayContainedBy()
         */
        public function orWhereBooleanArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotBooleanArrayContainedBy()
         */
        public function whereNotBooleanArrayContainedBy($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotBooleanArrayContainedBy()
         */
        public function orWhereNotBooleanArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL BOOLEAN[] 数组交集查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereBooleanArrayOverlaps()
         */
        public function whereBooleanArrayOverlaps($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereBooleanArrayOverlaps()
         */
        public function orWhereBooleanArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotBooleanArrayOverlaps()
         */
        public function whereNotBooleanArrayOverlaps($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotBooleanArrayOverlaps()
         */
        public function orWhereNotBooleanArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL DOUBLE PRECISION[] 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereDoubleArrayContains()
         */
        public function whereDoubleArrayContains($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereDoubleArrayContains()
         */
        public function orWhereDoubleArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotDoubleArrayContains()
         */
        public function whereNotDoubleArrayContains($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotDoubleArrayContains()
         */
        public function orWhereNotDoubleArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL DOUBLE PRECISION[] 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereDoubleArrayContainedBy()
         */
        public function whereDoubleArrayContainedBy($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereDoubleArrayContainedBy()
         */
        public function orWhereDoubleArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotDoubleArrayContainedBy()
         */
        public function whereNotDoubleArrayContainedBy($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotDoubleArrayContainedBy()
         */
        public function orWhereNotDoubleArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL DOUBLE PRECISION[] 数组交集查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereDoubleArrayOverlaps()
         */
        public function whereDoubleArrayOverlaps($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereDoubleArrayOverlaps()
         */
        public function orWhereDoubleArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotDoubleArrayOverlaps()
         */
        public function whereNotDoubleArrayOverlaps($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotDoubleArrayOverlaps()
         */
        public function orWhereNotDoubleArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL REAL[] (单精度浮点) 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereFloatArrayContains()
         */
        public function whereFloatArrayContains($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereFloatArrayContains()
         */
        public function orWhereFloatArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotFloatArrayContains()
         */
        public function whereNotFloatArrayContains($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotFloatArrayContains()
         */
        public function orWhereNotFloatArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL REAL[] 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereFloatArrayContainedBy()
         */
        public function whereFloatArrayContainedBy($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereFloatArrayContainedBy()
         */
        public function orWhereFloatArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotFloatArrayContainedBy()
         */
        public function whereNotFloatArrayContainedBy($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotFloatArrayContainedBy()
         */
        public function orWhereNotFloatArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL REAL[] 数组交集查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereFloatArrayOverlaps()
         */
        public function whereFloatArrayOverlaps($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereFloatArrayOverlaps()
         */
        public function orWhereFloatArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotFloatArrayOverlaps()
         */
        public function whereNotFloatArrayOverlaps($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotFloatArrayOverlaps()
         */
        public function orWhereNotFloatArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL UUID[] 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereUuidArrayContains()
         */
        public function whereUuidArrayContains($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereUuidArrayContains()
         */
        public function orWhereUuidArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotUuidArrayContains()
         */
        public function whereNotUuidArrayContains($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotUuidArrayContains()
         */
        public function orWhereNotUuidArrayContains($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL UUID[] 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereUuidArrayContainedBy()
         */
        public function whereUuidArrayContainedBy($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereUuidArrayContainedBy()
         */
        public function orWhereUuidArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotUuidArrayContainedBy()
         */
        public function whereNotUuidArrayContainedBy($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotUuidArrayContainedBy()
         */
        public function orWhereNotUuidArrayContainedBy($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 添加 PostgreSQL UUID[] 数组交集查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereUuidArrayOverlaps()
         */
        public function whereUuidArrayOverlaps($column, $value, $boolean = 'and', $not = false): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereUuidArrayOverlaps()
         */
        public function orWhereUuidArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotUuidArrayOverlaps()
         */
        public function whereNotUuidArrayOverlaps($column, $value, $boolean = 'and'): \Illuminate\Database\Query\Builder
        {
        }

        /**
         * 
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotUuidArrayOverlaps()
         */
        public function orWhereNotUuidArrayOverlaps($column, $value): \Illuminate\Database\Query\Builder
        {
        }
    }
}

namespace Illuminate\Database\Query\Grammars {

    /**
     * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin
     */
    class Grammar
    {
        /**
         * 编译 WHERE PostgreSQL INTEGER[] 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereIntArrayContains()
         */
        public function whereIntArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL INTEGER[] 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereIntArrayContainedBy()
         */
        public function whereIntArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL INTEGER[] 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereIntArrayOverlaps()
         */
        public function whereIntArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL BIGINT[] 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereBigIntArrayContains()
         */
        public function whereBigIntArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL BIGINT[] 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereBigIntArrayContainedBy()
         */
        public function whereBigIntArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL BIGINT[] 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereBigIntArrayOverlaps()
         */
        public function whereBigIntArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL SMALLINT[] 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereSmallIntArrayContains()
         */
        public function whereSmallIntArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL SMALLINT[] 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereSmallIntArrayContainedBy()
         */
        public function whereSmallIntArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL SMALLINT[] 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereSmallIntArrayOverlaps()
         */
        public function whereSmallIntArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL TEXT[] 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereTextArrayContains()
         */
        public function whereTextArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL TEXT[] 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereTextArrayContainedBy()
         */
        public function whereTextArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL TEXT[] 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereTextArrayOverlaps()
         */
        public function whereTextArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL BOOLEAN[] 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereBooleanArrayContains()
         */
        public function whereBooleanArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL BOOLEAN[] 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereBooleanArrayContainedBy()
         */
        public function whereBooleanArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL BOOLEAN[] 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereBooleanArrayOverlaps()
         */
        public function whereBooleanArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL DOUBLE PRECISION[] 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereDoubleArrayContains()
         */
        public function whereDoubleArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL DOUBLE PRECISION[] 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereDoubleArrayContainedBy()
         */
        public function whereDoubleArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL DOUBLE PRECISION[] 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereDoubleArrayOverlaps()
         */
        public function whereDoubleArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL REAL[] 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereFloatArrayContains()
         */
        public function whereFloatArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL REAL[] 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereFloatArrayContainedBy()
         */
        public function whereFloatArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL REAL[] 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereFloatArrayOverlaps()
         */
        public function whereFloatArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL UUID[] 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereUuidArrayContains()
         */
        public function whereUuidArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL UUID[] 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereUuidArrayContainedBy()
         */
        public function whereUuidArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL UUID[] 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereUuidArrayOverlaps()
         */
        public function whereUuidArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }
    }
}

namespace Illuminate\Http {

    /**
     * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin
     */
    class Request
    {
        /**
         * 获取客户端版本.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::getClientVersion()
         */
        public function getClientVersion(): ?string
        {
        }

        /**
         * 获取客户端的随机字符串.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::getClientNonce()
         */
        public function getClientNonce(): ?string
        {
        }

        /**
         * 获取客户端的签名字符串.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::getClientSignature()
         */
        public function getClientSignature(): ?string
        {
        }

        /**
         * 获取客户端的所有请求头.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::getClientHeaders()
         */
        public function getClientHeaders(): \Symfony\Component\HttpFoundation\HeaderBag
        {
        }

        /**
         * 获取客户端日期
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::getDate()
         */
        public function getDate(): ?string
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::getClientDate()
         */
        public function getClientDate(): ?string
        {
        }

        /**
         * 获取agent检测.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::getUserAgentDetect()
         */
        public function getUserAgentDetect(): \Jenssegers\Agent\Agent
        {
        }

        /**
         * 判断是否在微信客户端内.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::isWeChat()
         */
        public function isWeChat(): bool
        {
        }

        /**
         * 判断是否在微信客户端内.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::isWeChatMiniProgram()
         */
        public function isWeChatMiniProgram(): bool
        {
        }

        /**
         * 判断是否在postmen.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::isPostmen()
         */
        public function isPostmen(): bool
        {
        }

        /**
         * 判断请求是否来自指定版本的客户端.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::isEqClientVersion()
         */
        public function isEqClientVersion(string $version, ?int $length = null): bool
        {
        }

        /**
         * 判断请求是否来自大于指定版本的客户端.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::isLtClientVersion()
         */
        public function isLtClientVersion(string $version, bool $contain = false, ?int $length = null): bool
        {
        }

        /**
         * 判断请求是否来自小于指定版本的客户端.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::isGtClientVersion()
         */
        public function isGtClientVersion(string $version, bool $contain = false, ?int $length = null): bool
        {
        }

        /**
         * 获取最后一级目录.
         * @see \HughCube\Laravel\Knight\Mixin\Http\RequestMixin::getLastDirectory()
         */
        public function getLastDirectory(): ?string
        {
        }
    }
}

namespace Carbon {

    /**
     * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin
     */
    class Carbon
    {
        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::tryParse()
         */
        public function tryParse($date = null, $tz = null): ?\Carbon\Carbon
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::getTimestampAsFloat()
         */
        public function getTimestampAsFloat(): float
        {
        }

        /**
         * Mainly used for BC Math extensions.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::getTimestampAsString()
         */
        public function getTimestampAsString(): string
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::toRfc3339ExtendedString()
         */
        public function toRfc3339ExtendedString(): string
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::toChineseDate()
         */
        public function toChineseDate(): string
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::try()
         */
        public function try(callable $callable, $default = null): mixed
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::tryCreateFromFormat()
         */
        public function tryCreateFromFormat($format, $time, $timezone = null): mixed
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::tryParseDate()
         */
        public function tryParseDate($date): mixed
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CarbonMixin::tryCreateFromFormats()
         */
        public function tryCreateFromFormats($date, $formats): mixed
        {
        }
    }
}

namespace Illuminate\Support {

    /**
     * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin
     */
    class Collection
    {
        /**
         * 根据回调方法检查是否存在指定元素.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::hasByCallable()
         */
        public function hasByCallable(callable $key): mixed
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::hasAnyValues()
         */
        public function hasAnyValues($values, bool $strict = false): mixed
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::hasAllValues()
         */
        public function hasAllValues($values, bool $strict = false): mixed
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::hasValue()
         */
        public function hasValue($needle, $strict = false): mixed
        {
        }

        /**
         * 是否是索引数组.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::isIndexed()
         */
        public function isIndexed(bool $consecutive = true): mixed
        {
        }

        /**
         * 返回指定元素之后的所有元素.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::afterFirstItems()
         */
        public function afterFirstItems($value = null, $withBeacon = false, $strict = false): mixed
        {
        }

        /**
         * 返回指定元素之后的所有元素.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::afterLastItems()
         */
        public function afterLastItems($value = null, $withBeacon = false, $strict = false): mixed
        {
        }

        /**
         * 过滤元素直到满足$stop.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::filterWithStop()
         */
        public function filterWithStop(callable $stop, $withStopItem = false): mixed
        {
        }

        /**
         * pluck指定set(1,2,3,4)元素, 并且合并后在分割为Collection.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::pluckAndMergeSetColumn()
         */
        public function pluckAndMergeSetColumn($name, $separator = ',', $filter = null): mixed
        {
        }

        /**
         * 合并指定列中的数组元素.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::pluckAndMergeArrayColumn()
         */
        public function pluckAndMergeArrayColumn($name): mixed
        {
        }

        /**
         * 收集指定数组keys, 组合成一个新的collection.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::onlyArrayKeys()
         */
        public function onlyArrayKeys($keys = []): mixed
        {
        }

        /**
         * 收集指定属性的指定值, 组合成一个新的collection.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::onlyColumnValues()
         */
        public function onlyColumnValues($values, $name = null, bool $strict = false): mixed
        {
        }

        /**
         * 满足条件在执行过滤.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::whenFilter()
         */
        public function whenFilter($when, callable $callable): mixed
        {
        }

        /**
         * map int.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::mapInt()
         */
        public function mapInt(): mixed
        {
        }

        /**
         * map string.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::mapString()
         */
        public function mapString(): mixed
        {
        }

        /**
         * explode.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::explode()
         */
        public function explode(string $separator, string $string, int $limit = 9223372036854775807): mixed
        {
        }

        /**
         * split.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::splitWhitespace()
         */
        public function splitWhitespace(string $separator, string $pattern = '/\\s+/', int $limit = -1): mixed
        {
        }

        /**
         * split ,.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::splitComma()
         */
        public function splitComma(string $separator, string $pattern = '/[,，]/', int $limit = -1): mixed
        {
        }

        /**
         * split \/／.
         * @see \HughCube\Laravel\Knight\Mixin\Support\CollectionMixin::splitSlash()
         */
        public function splitSlash(string $separator, string $pattern = '#[\\/／]#', int $limit = -1): mixed
        {
        }
    }

    /**
     * @see \HughCube\Laravel\Knight\Mixin\Support\StrMixin
     */
    class Str
    {
        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\StrMixin::afterLast()
         */
        public function afterLast($subject, $search): mixed
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\StrMixin::beforeLast()
         */
        public function beforeLast($subject, $search): mixed
        {
        }

        /**
         * @see \HughCube\Laravel\Knight\Mixin\Support\StrMixin::getMobilePattern()
         */
        public function getMobilePattern(): mixed
        {
        }
    }
}
