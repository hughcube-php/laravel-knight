<?php

namespace HughCube\Laravel\Knight\Database\Eloquent\Traits;

use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * 分区表支持 Trait
 *
 * 为分区表模型自动在 update/delete 的 WHERE 子句中加入分区键，避免全分区扫描
 *
 * 使用方式：
 * - use PartitionKey;
 * - 定义常量 PARTITION_KEY_COLUMNS = ['tenant_id'] 或覆写 partitionKeyColumns() 方法
 *
 * @mixin EloquentModel
 */
trait PartitionKey
{
    /**
     * 获取分区键字段列表
     *
     * @return string[]
     */
    public function partitionKeyColumns(): array
    {
        return [];
    }

    /**
     * 为 setKeysForSaveQuery 添加分区键条件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQueryFromPartitionKey($query)
    {
        foreach ($this->partitionKeyColumns() as $column) {
            $query->where($column, $this->getOriginal($column));
        }

        return $query;
    }
}
