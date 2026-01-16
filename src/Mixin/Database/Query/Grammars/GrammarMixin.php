<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/3
 * Time: 15:01.
 */

namespace HughCube\Laravel\Knight\Mixin\Database\Query\Grammars;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use RuntimeException;

/**
 * Laravel SQL Grammar 的扩展 Mixin.
 *
 * 为 Laravel Query Grammar 提供额外的 SQL 编译方法扩展。
 *
 * 使用方式:
 *   在 ServiceProvider 中注册: Grammar::mixin(new GrammarMixin());
 *
 * 安全说明:
 *   - 使用 $this->parameter() 生成参数占位符，防止 SQL 注入
 *   - 列名通过 wrapJsonFieldAndPath() 正确转义处理
 *   - 遵循 Laravel 的标准 Grammar 编译模式
 *
 * @mixin Grammar
 * @property-read Grammar $connection
 * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin 对应的 Builder 扩展
 */
class GrammarMixin
{
    /**
     * 编译 WHERE JSON_OVERLAPS 条件为 SQL 片段.
     *
     * 此方法由 Laravel 的查询编译器自动调用，将 where 条件数组编译为 SQL。
     * 方法名必须为 "where{Type}"，其中 Type 对应 Builder 中设置的 type 值。
     * MySQL 8.0.17+ or PostgreSQL (jsonb emulation).
     *
     * JSON_OVERLAPS() 函数用于检查两个 JSON 数组是否有重叠元素（交集）。
     *
     * 生成的 SQL 示例:
     *   - whereJsonOverlaps('tags', ['a', 'b'])
     *     => json_overlaps(`tags`, ?)
     *
     *   - whereJsonOverlaps('data->tags', ['a'], 'and', true)
     *     => not json_overlaps(json_extract(`data`, '$.tags'), ?)
     *
     * @return Closure(Builder $query, array $where): string
     *
     * @link https://dev.mysql.com/doc/refman/8.0/en/json-search-functions.html#function_json-overlaps
     */
    public function whereJsonOverlaps(): Closure
    {
        return function (Builder $query, $where) {
            $not = $where['not'] ? 'not ' : '';

            /** @phpstan-ignore-next-line */
            return $not . $this->compileJsonOverlaps($where['column'], $this->parameter($where['value']));
        };
    }

    /**
     * 编译 JSON_OVERLAPS() 函数调用.
     *
     * 生成 MySQL JSON_OVERLAPS(json_doc1, json_doc2) 函数调用的 SQL。
     *
     * JSON 路径处理:
     *   - 'column'           => json_overlaps(`column`, ?)
     *   - 'column->key'      => json_overlaps(json_extract(`column`, '$.key'), ?)
     *   - 'column->a->b'     => json_overlaps(json_extract(`column`, '$.a.b'), ?)
     *
     * @return Closure(string $column, string $value): string
     */
    public function compileJsonOverlaps(): Closure
    {
        return function ($column, $value) {
            $driver = null;
            if (isset($this->connection) && method_exists($this->connection, 'getDriverName')) {
                $driver = $this->connection->getDriverName();
            }

            if ($driver === 'pgsql') {
                $column = str_replace('->>', '->', $this->wrap($column));
                $lhs = '(' . $column . ')::jsonb';
                $rhs = $value . '::jsonb';
                $alias = 'json_overlaps_values';

                return 'exists (select 1 from (select ' . $rhs . ' as v, ' . $lhs . ' as lhs) as ' . $alias . ' where case'
                    . " when jsonb_typeof({$alias}.lhs) = 'object' and jsonb_typeof({$alias}.v) = 'object' then "
                    . 'exists (select 1 from jsonb_each(' . $alias . '.lhs) l join jsonb_each(' . $alias . '.v) r on l.key = r.key and l.value = r.value)'
                    . " when jsonb_typeof({$alias}.lhs) = 'array' and jsonb_typeof({$alias}.v) = 'array' then "
                    . 'exists (select 1 from jsonb_array_elements(' . $alias . '.lhs) l join jsonb_array_elements(' . $alias . '.v) r on l = r)'
                    . " when jsonb_typeof({$alias}.lhs) = 'array' then "
                    . 'exists (select 1 from jsonb_array_elements(' . $alias . '.lhs) l where l = ' . $alias . '.v)'
                    . " when jsonb_typeof({$alias}.v) = 'array' then "
                    . 'exists (select 1 from jsonb_array_elements(' . $alias . '.v) r where r = ' . $alias . '.lhs)'
                    . ' else ' . $alias . '.lhs = ' . $alias . '.v end)';
            }

            if ($driver === 'mysql' || $driver === 'mariadb') {
                /** @phpstan-ignore-next-line */
                [$field, $path] = $this->wrapJsonFieldAndPath($column);

                if ($path !== '') {
                    $field = 'json_extract(' . $field . $path . ')';
                }

                return 'json_overlaps(' . $field . ', ' . $value . ')';
            }

            throw new RuntimeException('This database engine does not support JSON overlaps operations.');
        };
    }

    /**
     * 编译 WHERE PostgreSQL 数组包含条件 (@>) 为 SQL 片段.
     *
     * PostgreSQL 数组包含操作符 (@>) 检查左侧数组是否包含右侧数组的所有元素。
     * 使用 ARRAY[?, ?, ?]::type[] 格式，每个元素独立绑定，避免 SQL 注入风险。
     *
     * 生成的 SQL 示例:
     *   - whereArrayContains('tags', ['php', 'laravel'])
     *     => "tags" @> ARRAY[?, ?]::text[]
     *
     *   - whereArrayContains('ids', [1, 2, 3])
     *     => "ids" @> ARRAY[?, ?, ?]::integer[]
     *
     *   - whereArrayContains('tags', ['php'], 'and', true)
     *     => not ("tags" @> ARRAY[?]::text[])
     *
     * @return Closure(Builder $query, array $where): string
     *
     * @link https://www.postgresql.org/docs/current/functions-array.html
     */
    public function whereArrayContains(): Closure
    {
        return function (Builder $query, $where) {
            $not = $where['not'] ? 'not ' : '';
            /** @phpstan-ignore-next-line */
            $column = $this->wrap($where['column']);
            /** @phpstan-ignore-next-line */
            $arrayExpr = $this->compilePgArrayExpression($where['value'], $where['arrayType'] ?? null);

            return $not . '(' . $column . ' @> ' . $arrayExpr . ')';
        };
    }

    /**
     * 编译 WHERE PostgreSQL 数组被包含条件 (<@) 为 SQL 片段.
     *
     * PostgreSQL 数组被包含操作符 (<@) 检查左侧数组是否被右侧数组包含。
     * 使用 ARRAY[?, ?, ?]::type[] 格式，每个元素独立绑定，避免 SQL 注入风险。
     *
     * 生成的 SQL 示例:
     *   - whereArrayContainedBy('tags', ['php', 'laravel', 'mysql'])
     *     => "tags" <@ ARRAY[?, ?, ?]::text[]
     *
     *   - whereArrayContainedBy('tags', ['php'], 'and', true)
     *     => not ("tags" <@ ARRAY[?]::text[])
     *
     * @return Closure(Builder $query, array $where): string
     *
     * @link https://www.postgresql.org/docs/current/functions-array.html
     */
    public function whereArrayContainedBy(): Closure
    {
        return function (Builder $query, $where) {
            $not = $where['not'] ? 'not ' : '';
            /** @phpstan-ignore-next-line */
            $column = $this->wrap($where['column']);
            /** @phpstan-ignore-next-line */
            $arrayExpr = $this->compilePgArrayExpression($where['value'], $where['arrayType'] ?? null);

            return $not . '(' . $column . ' <@ ' . $arrayExpr . ')';
        };
    }

    /**
     * 编译 WHERE PostgreSQL 数组重叠条件 (&&) 为 SQL 片段.
     *
     * PostgreSQL 数组重叠操作符 (&&) 检查两个数组是否有交集（共同元素）。
     * 使用 ARRAY[?, ?, ?]::type[] 格式，每个元素独立绑定，避免 SQL 注入风险。
     *
     * 生成的 SQL 示例:
     *   - whereArrayOverlaps('tags', ['php', 'laravel'])
     *     => "tags" && ARRAY[?, ?]::text[]
     *
     *   - whereArrayOverlaps('tags', ['php'], 'and', true)
     *     => not ("tags" && ARRAY[?]::text[])
     *
     * @return Closure(Builder $query, array $where): string
     *
     * @link https://www.postgresql.org/docs/current/functions-array.html
     */
    public function whereArrayOverlaps(): Closure
    {
        return function (Builder $query, $where) {
            $not = $where['not'] ? 'not ' : '';
            /** @phpstan-ignore-next-line */
            $column = $this->wrap($where['column']);
            /** @phpstan-ignore-next-line */
            $arrayExpr = $this->compilePgArrayExpression($where['value'], $where['arrayType'] ?? null);

            return $not . '(' . $column . ' && ' . $arrayExpr . ')';
        };
    }

    /**
     * 编译 PostgreSQL ARRAY[?, ?, ?]::type[] 表达式.
     *
     * 根据值的 PHP 类型自动推断 PostgreSQL 数组类型：
     *   - int     => integer[]
     *   - float   => double precision[]
     *   - bool    => boolean[]
     *   - string  => text[]
     *
     * @return Closure(array $values, string|null $arrayType): string
     */
    public function compilePgArrayExpression(): Closure
    {
        return function (array $values, ?string $arrayType = null): string {
            $count = count($values);

            if ($count === 0) {
                $type = $arrayType ?? 'text';

                return 'ARRAY[]::' . $type . '[]';
            }

            $placeholders = implode(', ', array_fill(0, $count, '?'));

            if ($arrayType === null) {
                $firstValue = reset($values);
                if (is_int($firstValue)) {
                    $arrayType = 'integer';
                } elseif (is_float($firstValue)) {
                    $arrayType = 'double precision';
                } elseif (is_bool($firstValue)) {
                    $arrayType = 'boolean';
                } else {
                    $arrayType = 'text';
                }
            }

            return 'ARRAY[' . $placeholders . ']::' . $arrayType . '[]';
        };
    }
}
