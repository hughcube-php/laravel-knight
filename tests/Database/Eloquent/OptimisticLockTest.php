<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Exceptions\OptimisticLockException;
use HughCube\Laravel\Knight\Tests\Database\Eloquent\TestOptimisticLockModel;
use HughCube\Laravel\Knight\Tests\Database\Eloquent\TestOptimisticLockModelNoAutoIncrement;
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

        // 验证数据库中的实际数据
        $dbModel = TestOptimisticLockModel::find($model->id);
        $this->assertNotNull($dbModel);
        $this->assertEquals(1, $dbModel->data_version);
        $this->assertEquals('Test Item', $dbModel->name);
    }

    public function test_it_increments_data_version_on_successful_update()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        $model->name = 'Updated Item';
        $model->save();
        $this->assertEquals(2, $model->data_version);

        // 验证数据库中的实际数据
        $dbModel = TestOptimisticLockModel::find($model->id);
        $this->assertEquals(2, $dbModel->data_version);
        $this->assertEquals('Updated Item', $dbModel->name);

        $model->name = 'Updated Item 2';
        $model->save();
        $this->assertEquals(3, $model->data_version);

        // 再次验证数据库
        $dbModel->refresh();
        $this->assertEquals(3, $dbModel->data_version);
        $this->assertEquals('Updated Item 2', $dbModel->name);
    }

    public function test_it_throws_exception_on_optimistic_lock_failure()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        // 模拟另一个进程修改了数据，导致版本号不匹配
        TestOptimisticLockModel::where('id', $model->id)->update(['name' => 'Modified by another', 'data_version' => 2]);

        // 验证数据库中的版本号确实已更改
        $dbModel = TestOptimisticLockModel::find($model->id);
        $this->assertEquals(2, $dbModel->data_version);
        $this->assertEquals('Modified by another', $dbModel->name);

        // 但模型实例中的原始版本号仍然是 1
        $this->assertEquals(1, $model->getOriginal('data_version'));

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
        // 禁用乐观锁后，自动递增仍然生效，所以 data_version 从 2 变成 3
        $this->assertEquals(3, $model->data_version);
    }

    public function test_it_auto_increments_data_version_when_lock_disabled()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        $model->disableOptimisticLock(); // 禁用乐观锁，但自动递增仍然生效

        $model->name = 'Updated Item';
        $model->save();
        $this->assertEquals(2, $model->data_version);

        $model->name = 'Updated Item 2';
        $model->save();
        $this->assertEquals(3, $model->data_version);
    }

    public function test_it_does_not_increment_when_auto_increment_disabled()
    {
        $model = TestOptimisticLockModelNoAutoIncrement::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        $model->name = 'Updated Item';
        $model->save();
        // 禁用自动递增后，data_version 不变
        $this->assertEquals(1, $model->data_version);
    }

    public function test_bulk_update_does_not_trigger_optimistic_lock()
    {
        // 创建测试数据
        $model1 = TestOptimisticLockModel::create(['name' => 'Item 1']);
        $model2 = TestOptimisticLockModel::create(['name' => 'Item 2']);

        // 批量更新应该正常工作，不受乐观锁影响
        $affected = TestOptimisticLockModel::where('name', 'like', 'Item%')->update(['name' => 'Updated']);
        $this->assertEquals(2, $affected);

        // 验证数据库中的实际数据已更新
        $this->assertEquals('Updated', TestOptimisticLockModel::find($model1->id)->name);
        $this->assertEquals('Updated', TestOptimisticLockModel::find($model2->id)->name);
    }

    public function test_bulk_update_with_zero_affected_rows_does_not_throw()
    {
        TestOptimisticLockModel::create(['name' => 'Test Item']);

        // 批量更新没有匹配的行时，不应该抛出异常
        $affected = TestOptimisticLockModel::where('name', 'NonExistent')->update(['name' => 'Updated']);
        $this->assertEquals(0, $affected);
    }

    public function test_enable_optimistic_lock_after_disable()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);

        // 禁用乐观锁
        $model->disableOptimisticLock();
        $this->assertFalse($model->isOptimisticLockEnabled());

        // 重新启用乐观锁
        $model->enableOptimisticLock();
        $this->assertTrue($model->isOptimisticLockEnabled());

        // 模拟版本冲突
        TestOptimisticLockModel::where('id', $model->id)->update(['data_version' => 99]);

        $model->name = 'Attempt to update';
        $this->expectException(OptimisticLockException::class);
        $model->save();
    }

    public function test_setKeysForSaveQuery_adds_version_condition()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        // 验证 SQL 包含版本条件
        $model->name = 'Updated';

        // 通过检查更新后数据库的版本号来间接验证
        $model->save();

        $freshModel = TestOptimisticLockModel::find($model->id);
        $this->assertEquals(2, $freshModel->data_version);
        $this->assertEquals('Updated', $freshModel->name);
    }

    public function test_builder_update_returns_affected_rows()
    {
        TestOptimisticLockModel::create(['name' => 'Item 1']);
        TestOptimisticLockModel::create(['name' => 'Item 2']);
        TestOptimisticLockModel::create(['name' => 'Other']);

        // 验证 update 方法返回正确的影响行数
        $affected = TestOptimisticLockModel::where('name', 'like', 'Item%')->update(['name' => 'Updated']);
        $this->assertEquals(2, $affected);
    }
}
