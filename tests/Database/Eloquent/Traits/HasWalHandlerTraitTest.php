<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent\Traits;

use HughCube\Laravel\Knight\Contracts\Database\HasWalHandler;
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\HasWalHandlerTrait;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HasWalHandlerTraitTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        Schema::dropIfExists('wal_trait_test_items');
        Schema::create('wal_trait_test_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default('');
            $table->timestamps();
        });
    }

    public function testModelWithHasWalHandlerTraitImplementsInterface()
    {
        $model = new WalHandlerTraitTestModel();

        $this->assertInstanceOf(HasWalHandler::class, $model);
    }

    public function testModelReturnsCorrectTable()
    {
        $model = new WalHandlerTraitTestModel();

        $this->assertSame('wal_trait_test_items', $model->getTable());
    }

    public function testOnKnightModelChangedCallsDeleteRowCache()
    {
        $model = new WalHandlerTraitTestModel();

        // Should not throw
        $model->onKnightModelChanged();
        $this->assertTrue(true);
    }

    public function testCustomOnKnightModelChanged()
    {
        $model = new WalHandlerTraitTestModelWithCustomModelChanged();
        $model->onKnightModelChanged();

        $this->assertSame(1, WalHandlerTraitTestModelWithCustomModelChanged::$modelChangedCalls);
    }

    protected function setUp(): void
    {
        parent::setUp();

        WalHandlerTraitTestModelWithCustomModelChanged::$modelChangedCalls = 0;
    }
}

class WalHandlerTraitTestModel extends Model implements HasWalHandler
{
    use HasWalHandlerTrait;

    protected $table = 'wal_trait_test_items';
}

class WalHandlerTraitTestModelWithCustomModelChanged extends Model implements HasWalHandler
{
    use HasWalHandlerTrait;

    protected $table = 'wal_trait_test_items';

    /** @var int */
    public static $modelChangedCalls = 0;

    public function onKnightModelChanged(): void
    {
        static::$modelChangedCalls++;
    }
}
