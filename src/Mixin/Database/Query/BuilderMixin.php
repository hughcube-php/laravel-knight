<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/3
 * Time: 15:03.
 */

namespace HughCube\Laravel\Knight\Mixin\Database\Query;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;

/**
 * Laravel Query Builder 的扩展 Mixin.
 *
 * 为 Laravel Query Builder 提供额外的查询方法扩展。
 *
 * 使用方式:
 *   在 ServiceProvider 中注册: Builder::mixin(new BuilderMixin());
 *
 * 安全说明:
 *   - 所有值都通过 Laravel 的参数绑定机制处理，防止 SQL 注入
 *   - 列名通过 Grammar 的标准方法转义处理
 *
 * @mixin Builder
 *
 * @see \HughCube\Laravel\Knight\Mixin\Database\Query\Grammars\GrammarMixin 对应的 Grammar 扩展
 */
class BuilderMixin
{
    /**
     * 添加 PostgreSQL 数组包含查询条件 (@>).
     *
     * 检查数组字段是否包含给定数组的所有元素。
     * 仅适用于 PostgreSQL 数据库。
     * 使用 ARRAY[?, ?, ?]::type[] 格式，每个元素独立绑定，避免 SQL 注入风险。
     *
     * 示例:
     *   // 查找 tags 包含 'php' 和 'laravel' 的记录
     *   $query->whereArrayContains('tags', ['php', 'laravel']);
     *   // SQL: WHERE "tags" @> ARRAY[?, ?]::text[]
     *
     *   // 查找拥有所有指定权限的用户（指定类型）
     *   $query->whereArrayContains('ids', [1, 2, 3], 'and', false, 'bigint');
     *   // SQL: WHERE "ids" @> ARRAY[?, ?, ?]::bigint[]
     *
     * @return Closure(string $column, array $value, string $boolean = 'and', bool $not = false, string|null $arrayType = null): static
     */
    public function whereArrayContains(): Closure
    {
        return function ($column, $value, $boolean = 'and', $not = false, $arrayType = null) {
            $type = 'ArrayContains';

            /** @phpstan-ignore-next-line */
            $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not', 'arrayType');

            if (!$value instanceof Expression) {
                foreach ((array) $value as $val) {
                    /** @phpstan-ignore-next-line */
                    $this->addBinding($val);
                }
            }

            return $this;
        };
    }

    /**
     * 添加 OR PostgreSQL 数组包含查询条件.
     *
     * 示例:
     *   $query->where('status', 'active')
     *         ->orWhereArrayContains('tags', ['vip']);
     *   // SQL: WHERE status = 'active' OR ("tags" @> '{vip}')
     *
     * @return Closure(string $column, array $value, string|null $arrayType = null): static
     */
    public function orWhereArrayContains(): Closure
    {
        return function ($column, $value, $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayContains($column, $value, 'or', false, $arrayType);
        };
    }

    /**
     * 添加 NOT PostgreSQL 数组包含查询条件.
     *
     * 检查数组字段不包含给定数组的所有元素。
     *
     * 示例:
     *   $query->whereNotArrayContains('tags', ['banned']);
     *   // SQL: WHERE NOT ("tags" @> '{banned}')
     *
     * @return Closure(string $column, array $value, string $boolean = 'and', string|null $arrayType = null): static
     */
    public function whereNotArrayContains(): Closure
    {
        return function ($column, $value, $boolean = 'and', $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayContains($column, $value, $boolean, true, $arrayType);
        };
    }

    /**
     * 添加 OR NOT PostgreSQL 数组包含查询条件.
     *
     * 示例:
     *   $query->where('status', 'active')
     *         ->orWhereNotArrayContains('tags', ['banned']);
     *   // SQL: WHERE status = 'active' OR NOT ("tags" @> '{banned}')
     *
     * @return Closure(string $column, array $value, string|null $arrayType = null): static
     */
    public function orWhereNotArrayContains(): Closure
    {
        return function ($column, $value, $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayContains($column, $value, 'or', true, $arrayType);
        };
    }

    /**
     * 添加 PostgreSQL 数组被包含查询条件 (<@).
     *
     * 检查数组字段是否被给定数组包含（字段中的所有元素都在给定数组中）。
     * 仅适用于 PostgreSQL 数据库。
     * 使用 ARRAY[?, ?, ?]::type[] 格式，每个元素独立绑定，避免 SQL 注入风险。
     *
     * 示例:
     *   // 查找 tags 的所有元素都在 ['php', 'laravel', 'mysql'] 中的记录
     *   $query->whereArrayContainedBy('tags', ['php', 'laravel', 'mysql']);
     *   // SQL: WHERE "tags" <@ ARRAY[?, ?, ?]::text[]
     *
     * @return Closure(string $column, array $value, string $boolean = 'and', bool $not = false, string|null $arrayType = null): static
     */
    public function whereArrayContainedBy(): Closure
    {
        return function ($column, $value, $boolean = 'and', $not = false, $arrayType = null) {
            $type = 'ArrayContainedBy';

            /** @phpstan-ignore-next-line */
            $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not', 'arrayType');

            if (!$value instanceof Expression) {
                foreach ((array) $value as $val) {
                    /** @phpstan-ignore-next-line */
                    $this->addBinding($val);
                }
            }

            return $this;
        };
    }

    /**
     * 添加 OR PostgreSQL 数组被包含查询条件.
     *
     * 示例:
     *   $query->where('status', 'active')
     *         ->orWhereArrayContainedBy('tags', ['php', 'laravel']);
     *   // SQL: WHERE status = 'active' OR ("tags" <@ '{php,laravel}')
     *
     * @return Closure(string $column, array $value, string|null $arrayType = null): static
     */
    public function orWhereArrayContainedBy(): Closure
    {
        return function ($column, $value, $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayContainedBy($column, $value, 'or', false, $arrayType);
        };
    }

    /**
     * 添加 NOT PostgreSQL 数组被包含查询条件.
     *
     * 示例:
     *   $query->whereNotArrayContainedBy('tags', ['php']);
     *   // SQL: WHERE NOT ("tags" <@ '{php}')
     *
     * @return Closure(string $column, array $value, string $boolean = 'and', string|null $arrayType = null): static
     */
    public function whereNotArrayContainedBy(): Closure
    {
        return function ($column, $value, $boolean = 'and', $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayContainedBy($column, $value, $boolean, true, $arrayType);
        };
    }

    /**
     * 添加 OR NOT PostgreSQL 数组被包含查询条件.
     *
     * 示例:
     *   $query->where('status', 'active')
     *         ->orWhereNotArrayContainedBy('tags', ['php']);
     *   // SQL: WHERE status = 'active' OR NOT ("tags" <@ '{php}')
     *
     * @return Closure(string $column, array $value, string|null $arrayType = null): static
     */
    public function orWhereNotArrayContainedBy(): Closure
    {
        return function ($column, $value, $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayContainedBy($column, $value, 'or', true, $arrayType);
        };
    }

    /**
     * 添加 PostgreSQL 数组重叠查询条件 (&&).
     *
     * 检查数组字段是否与给定数组有交集（有共同元素）。
     * 仅适用于 PostgreSQL 数据库。
     * 使用 ARRAY[?, ?, ?]::type[] 格式，每个元素独立绑定，避免 SQL 注入风险。
     *
     * 示例:
     *   // 查找 tags 与 ['php', 'laravel'] 有任意共同元素的记录
     *   $query->whereArrayOverlaps('tags', ['php', 'laravel']);
     *   // SQL: WHERE "tags" && ARRAY[?, ?]::text[]
     *
     *   // 查找拥有任意指定权限的用户
     *   $query->whereArrayOverlaps('permissions', ['admin', 'moderator']);
     *
     * @return Closure(string $column, array $value, string $boolean = 'and', bool $not = false, string|null $arrayType = null): static
     */
    public function whereArrayOverlaps(): Closure
    {
        return function ($column, $value, $boolean = 'and', $not = false, $arrayType = null) {
            $type = 'ArrayOverlaps';

            /** @phpstan-ignore-next-line */
            $this->wheres[] = compact('type', 'column', 'value', 'boolean', 'not', 'arrayType');

            if (!$value instanceof Expression) {
                foreach ((array) $value as $val) {
                    /** @phpstan-ignore-next-line */
                    $this->addBinding($val);
                }
            }

            return $this;
        };
    }

    /**
     * 添加 OR PostgreSQL 数组重叠查询条件.
     *
     * 示例:
     *   $query->where('status', 'active')
     *         ->orWhereArrayOverlaps('tags', ['vip', 'premium']);
     *   // SQL: WHERE status = 'active' OR ("tags" && '{vip,premium}')
     *
     * @return Closure(string $column, array $value, string|null $arrayType = null): static
     */
    public function orWhereArrayOverlaps(): Closure
    {
        return function ($column, $value, $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayOverlaps($column, $value, 'or', false, $arrayType);
        };
    }

    /**
     * 添加 NOT PostgreSQL 数组重叠查询条件.
     *
     * 检查数组字段与给定数组没有交集。
     *
     * 示例:
     *   $query->whereNotArrayOverlaps('tags', ['banned', 'deprecated']);
     *   // SQL: WHERE NOT ("tags" && '{banned,deprecated}')
     *
     * @return Closure(string $column, array $value, string $boolean = 'and', string|null $arrayType = null): static
     */
    public function whereNotArrayOverlaps(): Closure
    {
        return function ($column, $value, $boolean = 'and', $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayOverlaps($column, $value, $boolean, true, $arrayType);
        };
    }

    /**
     * 添加 OR NOT PostgreSQL 数组重叠查询条件.
     *
     * 示例:
     *   $query->where('status', 'active')
     *         ->orWhereNotArrayOverlaps('tags', ['banned']);
     *   // SQL: WHERE status = 'active' OR NOT ("tags" && '{banned}')
     *
     * @return Closure(string $column, array $value, string|null $arrayType = null): static
     */
    public function orWhereNotArrayOverlaps(): Closure
    {
        return function ($column, $value, $arrayType = null) {
            /** @phpstan-ignore-next-line */
            return $this->whereArrayOverlaps($column, $value, 'or', true, $arrayType);
        };
    }
}
