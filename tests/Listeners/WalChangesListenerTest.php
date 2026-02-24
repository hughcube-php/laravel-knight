<?php

namespace HughCube\Laravel\Knight\Tests\Listeners;

use HughCube\Laravel\Knight\Contracts\Database\HasWalHandler;
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\HasWalHandlerTrait;
use HughCube\Laravel\Knight\Events\WalChangesDetected;
use HughCube\Laravel\Knight\Listeners\WalChangesListener;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;

class WalChangesListenerTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        Schema::dropIfExists('wal_test_items');
        Schema::create('wal_test_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default('');
            $table->timestamps();
        });
    }

    public function testHandleWithExistingModels()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();

        $connection->table('wal_test_items')->insert(['name' => 'item1']);
        $connection->table('wal_test_items')->insert(['name' => 'item2']);

        $handler = new WalTestItem();
        $event = new WalChangesDetected($handler, [1, 2]);

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(2, WalTestItem::$modelChangedCount);
        $this->assertSame(2, WalTestItem::$walChangedCount);
    }

    public function testHandleWithDeletedModelsCreatesClone()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();

        $connection->table('wal_test_items')->insert(['name' => 'existing']);

        $handler = new WalTestItem();
        $event = new WalChangesDetected($handler, [1, 999]);

        WalTestItem::$modelChangedCount = 0;
        WalTestItem::$walChangedCount = 0;
        WalTestItem::$changedIds = [];

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(2, WalTestItem::$modelChangedCount);
        $this->assertSame(2, WalTestItem::$walChangedCount);
        $this->assertContains(1, WalTestItem::$changedIds);
        $this->assertContains(999, WalTestItem::$changedIds);
    }

    public function testHandleWithAllDeletedModels()
    {
        $handler = new WalTestItem();
        $event = new WalChangesDetected($handler, [100, 200, 300]);

        WalTestItem::$modelChangedCount = 0;
        WalTestItem::$walChangedCount = 0;
        WalTestItem::$changedIds = [];

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(3, WalTestItem::$modelChangedCount);
        $this->assertSame(3, WalTestItem::$walChangedCount);

        $this->assertContains(100, WalTestItem::$changedIds);
        $this->assertContains(200, WalTestItem::$changedIds);
        $this->assertContains(300, WalTestItem::$changedIds);
    }

    public function testHandleWithEmptyIds()
    {
        $handler = new WalTestItem();
        $event = new WalChangesDetected($handler, []);

        WalTestItem::$modelChangedCount = 0;
        WalTestItem::$walChangedCount = 0;

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(0, WalTestItem::$modelChangedCount);
        $this->assertSame(0, WalTestItem::$walChangedCount);
    }

    public function testHandlerWithCustomKeyName()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();
        $connection->table('wal_test_items')->insert(['name' => 'test']);

        $handler = new WalTestItemCustomKey();
        $event = new WalChangesDetected($handler, [1]);

        WalTestItemCustomKey::$modelChangedCount = 0;

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(1, WalTestItemCustomKey::$modelChangedCount);
    }

    public function testHandlerWithoutOnKnightWalChangedStillCallsModelChanged()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();
        $connection->table('wal_test_items')->insert(['name' => 'test']);

        $handler = new WalTestItemNoWalChanged();
        $event = new WalChangesDetected($handler, [1]);

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(1, WalTestItemNoWalChanged::$modelChangedCount);
    }

    public function testHandleCallsWalChangedBeforeModelChanged()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();
        $connection->table('wal_test_items')->insert(['name' => 'order_test']);

        $handler = new WalTestItemCallOrder();
        $event = new WalChangesDetected($handler, [1]);

        WalTestItemCallOrder::$callOrder = [];

        $listener = new WalChangesListener();
        $listener->handle($event);

        // WalChangesListener calls onKnightWalChanged BEFORE onKnightModelChanged
        $this->assertSame(['walChanged', 'modelChanged'], WalTestItemCallOrder::$callOrder);
    }

    public function testHandleWithLargeNumberOfIds()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();

        // Insert 50 records
        for ($i = 1; $i <= 50; $i++) {
            $connection->table('wal_test_items')->insert(['name' => 'item' . $i]);
        }

        $handler = new WalTestItem();
        $ids = range(1, 50);
        $event = new WalChangesDetected($handler, $ids);

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(50, WalTestItem::$modelChangedCount);
        $this->assertSame(50, WalTestItem::$walChangedCount);
        $this->assertCount(50, WalTestItem::$changedIds);
    }

    public function testHandleMixedExistingAndDeletedModels()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();

        // Insert 3 records (ids 1, 2, 3)
        $connection->table('wal_test_items')->insert(['name' => 'exists1']);
        $connection->table('wal_test_items')->insert(['name' => 'exists2']);
        $connection->table('wal_test_items')->insert(['name' => 'exists3']);

        $handler = new WalTestItem();
        // ids 1, 2, 3 exist; 50, 60 do not (deleted)
        $event = new WalChangesDetected($handler, [1, 50, 2, 60, 3]);

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(5, WalTestItem::$modelChangedCount);
        $this->assertSame(5, WalTestItem::$walChangedCount);

        // Verify all IDs were processed (both existing and cloned)
        $this->assertContains(1, WalTestItem::$changedIds);
        $this->assertContains(2, WalTestItem::$changedIds);
        $this->assertContains(3, WalTestItem::$changedIds);
        $this->assertContains(50, WalTestItem::$changedIds);
        $this->assertContains(60, WalTestItem::$changedIds);
    }

    public function testHandleClonedModelHasCorrectPrimaryKey()
    {
        // All non-existent IDs -> all clones
        $handler = new WalTestItemTrackModels();
        $event = new WalChangesDetected($handler, [77, 88]);

        WalTestItemTrackModels::$models = [];

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertCount(2, WalTestItemTrackModels::$models);

        // Cloned models should have the correct primary key set
        $keys = array_map(function ($m) { return $m->getKey(); }, WalTestItemTrackModels::$models);
        $this->assertContains(77, $keys);
        $this->assertContains(88, $keys);
    }

    public function testHandleExistingModelHasData()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();
        $connection->table('wal_test_items')->insert(['name' => 'real_data']);

        $handler = new WalTestItemTrackModels();
        $event = new WalChangesDetected($handler, [1]);

        WalTestItemTrackModels::$models = [];

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertCount(1, WalTestItemTrackModels::$models);
        $model = WalTestItemTrackModels::$models[0];
        $this->assertSame(1, $model->getKey());
        $this->assertSame('real_data', $model->name);
    }

    public function testHandleWithSingleId()
    {
        /** @var \Illuminate\Database\Connection $connection */
        $connection = $this->app['db']->connection();
        $connection->table('wal_test_items')->insert(['name' => 'single']);

        $handler = new WalTestItem();
        $event = new WalChangesDetected($handler, [1]);

        $listener = new WalChangesListener();
        $listener->handle($event);

        $this->assertSame(1, WalTestItem::$modelChangedCount);
        $this->assertSame(1, WalTestItem::$walChangedCount);
    }

    protected function setUp(): void
    {
        parent::setUp();

        WalTestItem::$modelChangedCount = 0;
        WalTestItem::$walChangedCount = 0;
        WalTestItem::$changedIds = [];
        WalTestItemNoWalChanged::$modelChangedCount = 0;
        WalTestItemCallOrder::$callOrder = [];
        WalTestItemTrackModels::$models = [];
    }
}

/**
 * @property int    $id
 * @property string $name
 */
class WalTestItem extends Model implements HasWalHandler
{
    use HasWalHandlerTrait;

    protected $table = 'wal_test_items';

    protected $fillable = ['id', 'name'];

    /** @var int */
    public static $modelChangedCount = 0;

    /** @var int */
    public static $walChangedCount = 0;

    /** @var array */
    public static $changedIds = [];

    public function onKnightModelChanged(): void
    {
        static::$modelChangedCount++;
        static::$changedIds[] = $this->getKey();
    }

    public function onKnightWalChanged(): void
    {
        static::$walChangedCount++;
    }
}

/**
 * @property int $id
 */
class WalTestItemCustomKey extends Model implements HasWalHandler
{
    use HasWalHandlerTrait;

    protected $table = 'wal_test_items';

    protected $primaryKey = 'id';

    /** @var int */
    public static $modelChangedCount = 0;

    public function onKnightModelChanged(): void
    {
        static::$modelChangedCount++;
    }
}

class WalTestItemNoWalChanged extends Model implements HasWalHandler
{
    use HasWalHandlerTrait;

    protected $table = 'wal_test_items';

    /** @var int */
    public static $modelChangedCount = 0;

    public function onKnightModelChanged(): void
    {
        static::$modelChangedCount++;
    }
}

class WalTestItemCallOrder extends Model implements HasWalHandler
{
    use HasWalHandlerTrait;

    protected $table = 'wal_test_items';

    /** @var array */
    public static $callOrder = [];

    public function onKnightWalChanged(): void
    {
        static::$callOrder[] = 'walChanged';
    }

    public function onKnightModelChanged(): void
    {
        static::$callOrder[] = 'modelChanged';
    }
}

/**
 * @property int    $id
 * @property string $name
 */
class WalTestItemTrackModels extends Model implements HasWalHandler
{
    use HasWalHandlerTrait;

    protected $table = 'wal_test_items';

    protected $fillable = ['id', 'name'];

    /** @var array */
    public static $models = [];

    public function onKnightModelChanged(): void
    {
        static::$models[] = $this;
    }
}
