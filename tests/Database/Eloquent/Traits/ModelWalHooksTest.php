<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent\Traits;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModelWalHooksTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        Schema::dropIfExists('model_hooks_test');
        Schema::create('model_hooks_test', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default('');
            $table->timestamps();
        });
    }

    public function testOnKnightModelChangedIsCallable()
    {
        $model = new ModelWalHooksTestModel();

        $model->onKnightModelChanged();
        $this->assertTrue(true);
    }

    public function testOnKnightWalChangedIsCallable()
    {
        $model = new ModelWalHooksTestModel();

        $model->onKnightWalChanged();
        $this->assertTrue(true);
    }

    public function testOnKnightModelChangedDefaultCallsDeleteRowCache()
    {
        $model = new ModelWalHooksTestModelTracked();
        $model->onKnightModelChanged();

        $this->assertSame(1, ModelWalHooksTestModelTracked::$deleteRowCacheCalls);
    }

    public function testOnKnightWalChangedDefaultIsNoop()
    {
        $model = new ModelWalHooksTestModelTracked();
        $before = ModelWalHooksTestModelTracked::$deleteRowCacheCalls;

        $model->onKnightWalChanged();

        // Should not trigger deleteRowCache or any other side-effect
        $this->assertSame($before, ModelWalHooksTestModelTracked::$deleteRowCacheCalls);
    }

    public function testModelChangedCalledOnEloquentCreated()
    {
        ModelWalHooksTestModelTracked::$deleteRowCacheCalls = 0;

        $model = new ModelWalHooksTestModelTracked();
        $model->name = 'test';
        $model->save();

        // onKnightModelChanged -> deleteRowCache is called
        $this->assertGreaterThanOrEqual(1, ModelWalHooksTestModelTracked::$deleteRowCacheCalls);
    }

    public function testModelChangedCalledOnEloquentUpdated()
    {
        $model = new ModelWalHooksTestModelTracked();
        $model->name = 'test';
        $model->save();

        ModelWalHooksTestModelTracked::$deleteRowCacheCalls = 0;

        $model->name = 'updated';
        $model->save();

        $this->assertGreaterThanOrEqual(1, ModelWalHooksTestModelTracked::$deleteRowCacheCalls);
    }

    public function testModelChangedCalledOnEloquentDeleted()
    {
        $model = new ModelWalHooksTestModelTracked();
        $model->name = 'to_delete';
        $model->save();

        ModelWalHooksTestModelTracked::$deleteRowCacheCalls = 0;

        $model->delete();

        $this->assertGreaterThanOrEqual(1, ModelWalHooksTestModelTracked::$deleteRowCacheCalls);
    }

    protected function setUp(): void
    {
        parent::setUp();

        ModelWalHooksTestModelTracked::$deleteRowCacheCalls = 0;
    }
}

class ModelWalHooksTestModel extends Model
{
    protected $table = 'model_hooks_test';

    protected $fillable = ['name'];
}

class ModelWalHooksTestModelTracked extends Model
{
    protected $table = 'model_hooks_test';

    protected $fillable = ['name'];

    /** @var int */
    public static $deleteRowCacheCalls = 0;

    public function deleteRowCache(): bool
    {
        static::$deleteRowCacheCalls++;

        return true;
    }
}
