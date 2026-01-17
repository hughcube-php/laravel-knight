<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Database\Eloquent\Traits\OptimisticLock;
use Illuminate\Database\Eloquent\Model;

class TestOptimisticLockModel extends Model
{
    use OptimisticLock;

    protected $table = 'test_optimistic_lock_models'; // 定义表名
    protected $guarded = []; // 允许所有字段批量赋值
    public $timestamps = false; // 禁用时间戳，简化测试
}
