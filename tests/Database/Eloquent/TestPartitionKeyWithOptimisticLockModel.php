<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\OptimisticLock;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\PartitionKey;

class TestPartitionKeyWithOptimisticLockModel extends Model
{
    use PartitionKey;
    use OptimisticLock;

    protected $table = 'test_partition_key_lock_models';
    protected $guarded = [];
    public $timestamps = false;

    public function partitionKeyColumns(): array
    {
        return ['tenant_id'];
    }
}
