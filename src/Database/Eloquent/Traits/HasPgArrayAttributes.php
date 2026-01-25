<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use HughCube\Base\Base;
use Illuminate\Support\Collection;

/**
 * PostgreSQL 原生数组字段辅助 Trait
 *
 * 支持格式: {1,2,3} 和 {o:1,o:2}
 *
 * 使用方式:
 * 1. 在 Model 中使用此 Trait
 * 2. 默认会从 $casts 中移除值为 'ARRAY' 的项(因为 PostgreSQL 原生数组不需要 Laravel 的 cast)
 * 3. 如需保留 'ARRAY' casts，可在 Model 中定义 shouldPreserveArrayCasts() 方法返回 true
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPgArrayAttributes
{
    /**
     * 初始化 Trait，在 Model boot 时自动调用
     * 默认移除 casts 中值为 'ARRAY' 的项
     * 如 Model 定义了 shouldPreserveArrayCasts() 方法且返回 true 则跳过移除
     */
    public function initializeHasPgArrayAttributes(): void
    {
        if (!method_exists($this, 'shouldPreserveArrayCasts') || !$this->shouldPreserveArrayCasts()) {
            $this->casts = array_filter($this->casts, fn($cast) => $cast !== 'ARRAY');
        }
    }

    /**
     * 解析 int[]: {1,2,3} → [1,2,3]
     * 注意：为避免 bigint 溢出，超过 PHP_INT_MAX 的值保持为字符串
     */
    protected function parsePgIntArray($value): Collection
    {
        if (!is_string($value) || $value === '' || $value === '{}') {
            return Collection::make();
        }

        return Collection::make(explode(',', trim($value, '{}')))->map(function ($v) {
            $v = trim($v);
            // 检查是否在 PHP int 安全范围内
            if (is_numeric($v) && $v >= PHP_INT_MIN && $v <= PHP_INT_MAX) {
                return (int) $v;
            }
            // bigint 超出范围时保持字符串，避免精度损失
            return $v;
        });
    }

    /**
     * 解析简单 text[]: {o:1,o:2} → ['o:1','o:2']
     * 仅支持字母、数字、冒号、下划线、点、连字符
     */
    protected function parsePgSimpleTextArray($value): Collection
    {
        if (!is_string($value) || $value === '' || $value === '{}') {
            return Collection::make();
        }
        return Collection::make(explode(',', trim($value, '{}')));
    }

    /**
     * 序列化 int[]: [1,2,3] → {1,2,3}
     */
    protected function serializePgIntArray($value): string
    {
        return sprintf('{%s}', Collection::make($value)->filter(fn($v) => Base::isInteger($v))->map(fn($v) => Base::toString($v))->implode(','));
    }

    /**
     * 序列化简单 text[]: ['o:1','o:2'] → {o:1,o:2}
     * 支持字母、数字、冒号、下划线、点、连字符、斜杠、@、+、=、#、~
     * 不支持: 空格、逗号、花括号、引号、反斜杠（这些是 PostgreSQL 数组特殊字符）
     */
    protected function serializePgSimpleTextArray($value): string
    {
        return sprintf('{%s}', Collection::make($value)->filter(fn($v) => is_string($v) && preg_match('/^[a-zA-Z0-9:_.\/\-@+=#~]+$/', $v))->implode(','));
    }
}
