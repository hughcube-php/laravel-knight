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
    }
}

namespace Illuminate\Database\Query {

    /**
     * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin
     */
    class Builder
    {
        /**
         * 添加 PostgreSQL 数组包含查询条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereArrayContains()
         */
        public function whereArrayContains($column, $value, $boolean = 'and', $not = false, $arrayType = null): mixed
        {
        }

        /**
         * 添加 OR PostgreSQL 数组包含查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereArrayContains()
         */
        public function orWhereArrayContains($column, $value, $arrayType = null): mixed
        {
        }

        /**
         * 添加 NOT PostgreSQL 数组包含查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotArrayContains()
         */
        public function whereNotArrayContains($column, $value, $boolean = 'and', $arrayType = null): mixed
        {
        }

        /**
         * 添加 OR NOT PostgreSQL 数组包含查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotArrayContains()
         */
        public function orWhereNotArrayContains($column, $value, $arrayType = null): mixed
        {
        }

        /**
         * 添加 PostgreSQL 数组被包含查询条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereArrayContainedBy()
         */
        public function whereArrayContainedBy($column, $value, $boolean = 'and', $not = false, $arrayType = null): mixed
        {
        }

        /**
         * 添加 OR PostgreSQL 数组被包含查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereArrayContainedBy()
         */
        public function orWhereArrayContainedBy($column, $value, $arrayType = null): mixed
        {
        }

        /**
         * 添加 NOT PostgreSQL 数组被包含查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotArrayContainedBy()
         */
        public function whereNotArrayContainedBy($column, $value, $boolean = 'and', $arrayType = null): mixed
        {
        }

        /**
         * 添加 OR NOT PostgreSQL 数组被包含查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotArrayContainedBy()
         */
        public function orWhereNotArrayContainedBy($column, $value, $arrayType = null): mixed
        {
        }

        /**
         * 添加 PostgreSQL 数组重叠查询条件 (&&).
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereArrayOverlaps()
         */
        public function whereArrayOverlaps($column, $value, $boolean = 'and', $not = false, $arrayType = null): mixed
        {
        }

        /**
         * 添加 OR PostgreSQL 数组重叠查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereArrayOverlaps()
         */
        public function orWhereArrayOverlaps($column, $value, $arrayType = null): mixed
        {
        }

        /**
         * 添加 NOT PostgreSQL 数组重叠查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::whereNotArrayOverlaps()
         */
        public function whereNotArrayOverlaps($column, $value, $boolean = 'and', $arrayType = null): mixed
        {
        }

        /**
         * 添加 OR NOT PostgreSQL 数组重叠查询条件.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin::orWhereNotArrayOverlaps()
         */
        public function orWhereNotArrayOverlaps($column, $value, $arrayType = null): mixed
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
         * 编译 WHERE PostgreSQL 数组包含条件 (
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereArrayContains()
         */
        public function whereArrayContains(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL 数组被包含条件 (<
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereArrayContainedBy()
         */
        public function whereArrayContainedBy(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 WHERE PostgreSQL 数组重叠条件 (&&) 为 SQL 片段.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::whereArrayOverlaps()
         */
        public function whereArrayOverlaps(\Illuminate\Database\Query\Builder $query, $where): mixed
        {
        }

        /**
         * 编译 PostgreSQL ARRAY[?, ?, ?]::type[] 表达式.
         * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin::compilePgArrayExpression()
         */
        public function compilePgArrayExpression(array $values, ?string $arrayType = null): string
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
