<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Exceptions\OptimisticLockException;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class OptimisticLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 每次测试前创建表
        Schema::create('test_optimistic_lock_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedBigInteger('data_version')->default(1);
        });
    }

    protected function tearDown(): void
    {
        // 每次测试后删除表
        Schema::dropIfExists('test_optimistic_lock_models');

        parent::tearDown();
    }

    public function test_it_initializes_data_version_on_create()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);
    }

    public function test_it_increments_data_version_on_successful_update()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        $model->name = 'Updated Item';
        $model->save();
        $this->assertEquals(2, $model->data_version);

        $model->name = 'Updated Item 2';
        $model->save();
        $this->assertEquals(3, $model->data_version);
    }

    public function test_it_throws_exception_on_optimistic_lock_failure()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        // 模拟另一个进程修改了数据，导致版本号不匹配
        TestOptimisticLockModel::where('id', $model->id)->update(['name' => 'Modified by another', 'data_version' => 2]);

        $model->name = 'Attempt to update';

        $this->expectException(OptimisticLockException::class);
        $model->save(); // 应该抛出异常
    }

    public function test_it_does_not_throw_exception_when_optimistic_lock_is_disabled()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        // 模拟另一个进程修改了数据，导致版本号不匹配
        TestOptimisticLockModel::where('id', $model->id)->update(['name' => 'Modified by another', 'data_version' => 2]);

        $model->refresh(); // 从数据库中重新加载 Model 实例

        $model->name = 'Attempt to update with lock disabled';
        $model->disableOptimisticLock(); // 禁用乐观锁

        $model->save(); // 不应该抛出异常
        $this->assertEquals(2, $model->data_version);
    }
}
