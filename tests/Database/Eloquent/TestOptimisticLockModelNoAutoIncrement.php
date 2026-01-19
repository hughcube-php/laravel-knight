<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\OptimisticLock;

class TestOptimisticLockModelNoAutoIncrement extends Model
{
    use OptimisticLock;

    public const AUTO_INCREMENT_DATA_VERSION = false;

    protected $table = 'test_optimistic_lock_models';
    protected $guarded = [];
    public $timestamps = false;
}
