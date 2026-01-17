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
 *
 * @property-read Grammar $connection
 *
 * @see \HughCube\Laravel\Knight\Mixin\Database\Query\BuilderMixin 对应的 Builder 扩展
 */
class GrammarMixin
{
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

            $column = $this->wrap($where['column']);

            /** @phpstan-ignore-next-line */
            $arrayExpr = $this->compilePgArrayExpression($where['value'], $where['arrayType'] ?? null);

            /** @phpstan-ignore-next-line */
            return $not.'('.$column.' @> '.$arrayExpr.')';
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

            $column = $this->wrap($where['column']);

            /** @phpstan-ignore-next-line */
            $arrayExpr = $this->compilePgArrayExpression($where['value'], $where['arrayType'] ?? null);

            /** @phpstan-ignore-next-line */
            return $not.'('.$column.' <@ '.$arrayExpr.')';
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

            $column = $this->wrap($where['column']);

            /** @phpstan-ignore-next-line */
            $arrayExpr = $this->compilePgArrayExpression($where['value'], $where['arrayType'] ?? null);

            /** @phpstan-ignore-next-line */
            return $not.'('.$column.' && '.$arrayExpr.')';
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

                return 'ARRAY[]::'.$type.'[]';
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

            return 'ARRAY['.$placeholders.']::'.$arrayType.'[]';
        };
    }
}
