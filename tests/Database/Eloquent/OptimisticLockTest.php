<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Exceptions\OptimisticLockException;
use HughCube\Laravel\Knight\Tests\Database\Eloquent\TestOptimisticLockModel;
use HughCube\Laravel\Knight\Tests\Database\Eloquent\TestOptimisticLockModelCustomColumn;
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

        // 创建自定义版本字段表
        Schema::create('test_optimistic_lock_models_custom', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedBigInteger('version')->default(1);
        });
    }

    protected function tearDown(): void
    {
        // 每次测试后删除表
        Schema::dropIfExists('test_optimistic_lock_models');
        Schema::dropIfExists('test_optimistic_lock_models_custom');

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
        // 使用 useOptimisticLockSave 临时启用乐观锁检查并保存
        $model->useOptimisticLockSave(); // 应该抛出异常
    }

    public function test_it_does_not_throw_exception_when_optimistic_lock_is_disabled()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        // 默认情况下乐观锁检查是禁用的
        $this->assertFalse($model->isOptimisticLockEnabled());

        // 模拟另一个进程修改了数据，导致版本号不匹配
        TestOptimisticLockModel::where('id', $model->id)->update(['name' => 'Modified by another', 'data_version' => 2]);

        // 不刷新模型，直接更新（原始版本号仍为1，但数据库中是2）
        $model->name = 'Attempt to update with lock disabled';

        $model->save(); // 默认不启用乐观锁检查，不应该抛出异常
        // 自动递增仍然生效，所以 data_version 从 1 变成 2（模型内存中的值）
        $this->assertEquals(2, $model->data_version);
    }

    public function test_it_auto_increments_data_version_when_lock_disabled()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        // 默认乐观锁检查已禁用，但自动递增仍然生效
        $this->assertFalse($model->isOptimisticLockEnabled());

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

    public function test_enable_optimistic_lock_from_default_disabled()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);

        // 默认乐观锁检查是禁用的
        $this->assertFalse($model->isOptimisticLockEnabled());

        // 启用乐观锁
        $model->enableOptimisticLock();
        $this->assertTrue($model->isOptimisticLockEnabled());

        // 模拟版本冲突
        TestOptimisticLockModel::where('id', $model->id)->update(['data_version' => 99]);

        $model->name = 'Attempt to update';
        $this->expectException(OptimisticLockException::class);
        $model->save();
    }

    public function test_disable_optimistic_lock_after_enable()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);

        // 启用乐观锁
        $model->enableOptimisticLock();
        $this->assertTrue($model->isOptimisticLockEnabled());

        // 禁用乐观锁
        $model->disableOptimisticLock();
        $this->assertFalse($model->isOptimisticLockEnabled());

        // 模拟版本冲突
        TestOptimisticLockModel::where('id', $model->id)->update(['data_version' => 99]);

        // 禁用后不会抛出异常
        $model->name = 'Attempt to update';
        $model->save(); // 不应该抛出异常
        $this->assertEquals(2, $model->data_version);
    }

    public function test_setKeysForSaveQuery_adds_version_condition()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        // 启用乐观锁检查，这样 setKeysForSaveQuery 才会添加版本条件
        $model->enableOptimisticLock();

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

    public function test_useOptimisticLockSave_succeeds_without_conflict()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);
        $this->assertFalse($model->isOptimisticLockEnabled()); // 默认禁用

        $model->name = 'Updated Item';
        $result = $model->useOptimisticLockSave();

        $this->assertTrue($result);
        $this->assertEquals(2, $model->data_version);
        $this->assertFalse($model->isOptimisticLockEnabled()); // 保存后恢复为禁用状态

        // 验证数据库中的实际数据
        $dbModel = TestOptimisticLockModel::find($model->id);
        $this->assertEquals(2, $dbModel->data_version);
        $this->assertEquals('Updated Item', $dbModel->name);
    }

    public function test_useOptimisticLockSave_restores_previous_state_on_exception()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertFalse($model->isOptimisticLockEnabled());

        // 模拟版本冲突
        TestOptimisticLockModel::where('id', $model->id)->update(['data_version' => 99]);

        $model->name = 'Attempt to update';

        try {
            $model->useOptimisticLockSave();
            $this->fail('Expected OptimisticLockException was not thrown');
        } catch (OptimisticLockException $e) {
            // 即使抛出异常，乐观锁状态也应该恢复
            $this->assertFalse($model->isOptimisticLockEnabled());
        }
    }

    public function test_useOptimisticLockUpdate_succeeds_without_conflict()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);
        $this->assertFalse($model->isOptimisticLockEnabled());

        $result = $model->useOptimisticLockUpdate(['name' => 'Updated via Update']);

        $this->assertTrue($result);
        $this->assertEquals(2, $model->data_version);
        $this->assertFalse($model->isOptimisticLockEnabled()); // 更新后恢复为禁用状态

        // 验证数据库中的实际数据
        $dbModel = TestOptimisticLockModel::find($model->id);
        $this->assertEquals(2, $dbModel->data_version);
        $this->assertEquals('Updated via Update', $dbModel->name);
    }

    public function test_useOptimisticLockUpdate_throws_on_conflict()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertFalse($model->isOptimisticLockEnabled());

        // 模拟版本冲突
        TestOptimisticLockModel::where('id', $model->id)->update(['data_version' => 99]);

        $this->expectException(OptimisticLockException::class);
        $model->useOptimisticLockUpdate(['name' => 'Attempt to update']);
    }

    public function test_useOptimisticLockUpdate_restores_previous_state_on_exception()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertFalse($model->isOptimisticLockEnabled());

        // 模拟版本冲突
        TestOptimisticLockModel::where('id', $model->id)->update(['data_version' => 99]);

        try {
            $model->useOptimisticLockUpdate(['name' => 'Attempt to update']);
            $this->fail('Expected OptimisticLockException was not thrown');
        } catch (OptimisticLockException $e) {
            // 即使抛出异常，乐观锁状态也应该恢复
            $this->assertFalse($model->isOptimisticLockEnabled());
        }
    }

    public function test_custom_data_version_column()
    {
        $model = TestOptimisticLockModelCustomColumn::create(['name' => 'Test Item']);

        // 验证使用自定义字段名
        $this->assertEquals('version', TestOptimisticLockModelCustomColumn::lockDataVersionColumn());
        $this->assertEquals(1, $model->version);

        // 更新时版本号递增
        $model->name = 'Updated Item';
        $model->save();
        $this->assertEquals(2, $model->version);

        // 验证数据库中的实际数据
        $dbModel = TestOptimisticLockModelCustomColumn::find($model->id);
        $this->assertEquals(2, $dbModel->version);
    }

    public function test_custom_column_with_optimistic_lock_enabled()
    {
        $model = TestOptimisticLockModelCustomColumn::create(['name' => 'Test Item']);
        $model->enableOptimisticLock();

        // 模拟版本冲突
        TestOptimisticLockModelCustomColumn::where('id', $model->id)->update(['version' => 99]);

        $model->name = 'Attempt to update';
        $this->expectException(OptimisticLockException::class);
        $model->save();
    }

    public function test_create_with_custom_initial_version()
    {
        // 手动设置初始版本号
        $model = TestOptimisticLockModel::create(['name' => 'Test Item', 'data_version' => 10]);
        $this->assertEquals(10, $model->data_version);

        // 更新时从自定义版本号递增
        $model->name = 'Updated Item';
        $model->save();
        $this->assertEquals(11, $model->data_version);

        // 验证数据库
        $dbModel = TestOptimisticLockModel::find($model->id);
        $this->assertEquals(11, $dbModel->data_version);
    }

    public function test_enable_and_disable_return_self_for_chaining()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);

        // 验证链式调用返回模型实例
        $result1 = $model->enableOptimisticLock();
        $this->assertSame($model, $result1);

        $result2 = $model->disableOptimisticLock();
        $this->assertSame($model, $result2);

        // 链式调用示例
        $result3 = $model->enableOptimisticLock()->disableOptimisticLock()->enableOptimisticLock();
        $this->assertSame($model, $result3);
        $this->assertTrue($model->isOptimisticLockEnabled());
    }

    public function test_new_model_first_save_does_not_check_lock()
    {
        // 新模型在首次保存时不应该检查乐观锁
        $model = new TestOptimisticLockModel(['name' => 'Test Item']);
        $model->enableOptimisticLock();
        $this->assertTrue($model->isOptimisticLockEnabled());
        $this->assertFalse($model->exists);

        // 首次保存应该成功（不检查乐观锁条件）
        $model->save();
        $this->assertTrue($model->exists);
        $this->assertEquals(1, $model->data_version);
    }

    public function test_exception_message_contains_model_class_and_key()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $modelId = $model->id;

        // 模拟版本冲突
        TestOptimisticLockModel::where('id', $model->id)->update(['data_version' => 99]);

        $model->name = 'Attempt to update';

        try {
            $model->useOptimisticLockSave();
            $this->fail('Expected OptimisticLockException was not thrown');
        } catch (OptimisticLockException $e) {
            // 验证异常消息包含模型类名和主键
            $this->assertStringContainsString(TestOptimisticLockModel::class, $e->getMessage());
            $this->assertStringContainsString((string)$modelId, $e->getMessage());
        }
    }

    public function test_default_data_version_column_name()
    {
        // 默认使用 'data_version' 字段
        $this->assertEquals('data_version', TestOptimisticLockModel::lockDataVersionColumn());
    }

    public function test_default_model_data_version_value()
    {
        // 默认版本号为 1
        $this->assertEquals(1, TestOptimisticLockModel::defaultModelDataVersion());
    }

    public function test_is_auto_increment_data_version_default_true()
    {
        $model = new TestOptimisticLockModel();
        $this->assertTrue($model->isAutoIncrementDataVersion());
    }

    public function test_version_not_changed_when_no_dirty_attributes()
    {
        $model = TestOptimisticLockModel::create(['name' => 'Test Item']);
        $this->assertEquals(1, $model->data_version);

        // 不修改任何属性直接保存
        $model->save();

        // 由于没有脏属性，不会触发 updating 事件
        $dbModel = TestOptimisticLockModel::find($model->id);
        $this->assertEquals(1, $dbModel->data_version);
    }

    public function test_concurrent_update_simulation()
    {
        // 模拟并发更新场景
        $model1 = TestOptimisticLockModel::create(['name' => 'Original']);

        // 两个"用户"同时加载同一条记录
        $model2 = TestOptimisticLockModel::find($model1->id);

        // 用户1更新成功
        $model1->name = 'Updated by User 1';
        $model1->useOptimisticLockSave();
        $this->assertEquals(2, $model1->data_version);

        // 用户2尝试更新，应该失败
        $model2->name = 'Updated by User 2';
        $this->expectException(OptimisticLockException::class);
        $model2->useOptimisticLockSave();
    }

    public function test_refresh_after_conflict_allows_retry()
    {
        $model1 = TestOptimisticLockModel::create(['name' => 'Original']);
        $model2 = TestOptimisticLockModel::find($model1->id);

        // 用户1更新
        $model1->name = 'Updated by User 1';
        $model1->save();

        // 用户2更新失败后刷新模型
        $model2->name = 'Updated by User 2';

        try {
            $model2->useOptimisticLockSave();
            $this->fail('Expected OptimisticLockException was not thrown');
        } catch (OptimisticLockException $e) {
            // 刷新模型获取最新数据
            $model2->refresh();
            $this->assertEquals(2, $model2->data_version);
            $this->assertEquals('Updated by User 1', $model2->name);

            // 重新修改并保存
            $model2->name = 'Updated by User 2 after refresh';
            $model2->useOptimisticLockSave();
            $this->assertEquals(3, $model2->data_version);
        }
    }
}
