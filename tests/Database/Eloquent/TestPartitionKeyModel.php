<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\PartitionKey;

class TestPartitionKeyModel extends Model
{
    use PartitionKey;

    protected $table = 'test_partition_key_models';
    protected $guarded = [];
    public $timestamps = false;

    public function partitionKeyColumns(): array
    {
        return ['tenant_id'];
    }
}
