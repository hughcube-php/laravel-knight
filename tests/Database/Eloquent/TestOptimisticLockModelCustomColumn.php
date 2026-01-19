<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\OptimisticLock;

class TestOptimisticLockModelCustomColumn extends Model
{
    use OptimisticLock;

    public const DATA_VERSION = 'version';

    protected $table = 'test_optimistic_lock_models_custom';
    protected $guarded = [];
    public $timestamps = false;
}
