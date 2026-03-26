<?php

namespace HughCube\Laravel\Knight\Tests\Database\Wal;

use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Wal\WalChangeRecord;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WalChangeRecordTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        Schema::dropIfExists('wal_record_test_items');
        Schema::create('wal_record_test_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default('');
            $table->string('email')->default('');
            $table->timestamps();
        });
    }

    // ==================== Constructor & Getters ====================

    public function testConstructorAndGetters()
    {
        $record = new WalChangeRecord(
            'insert',
            'users',
            'users_2024',
            42,
            'id',
            ['id' => 42, 'name' => 'John'],
            [],
            WalRecordTestModel::class
        );

        $this->assertSame('insert', $record->getKind());
        $this->assertSame('users', $record->getTable());
        $this->assertSame('users_2024', $record->getPartitionTable());
        $this->assertSame(42, $record->getId());
        $this->assertSame('id', $record->getKeyName());
        $this->assertSame(['id' => 42, 'name' => 'John'], $record->getColumns());
        $this->assertSame([], $record->getOldColumns());
        $this->assertSame(WalRecordTestModel::class, $record->getModelClass());
    }

    public function testWithStringId()
    {
        $record = new WalChangeRecord(
            'insert',
            'items',
            null,
            'abc-def-123',
            'uuid',
            ['uuid' => 'abc-def-123', 'name' => 'Widget'],
            [],
            'stdClass'
        );

        $this->assertSame('abc-def-123', $record->getId());
        $this->assertSame('uuid', $record->getKeyName());
    }

    public function testWithNullPartitionTable()
    {
        $record = new WalChangeRecord('insert', 'users', null, 1, 'id', [], [], 'stdClass');

        $this->assertNull($record->getPartitionTable());
    }

    // ==================== Kind Helpers ====================

    public function testIsInsert()
    {
        $record = new WalChangeRecord('insert', 'users', null, 1, 'id', [], [], 'stdClass');

        $this->assertTrue($record->isInsert());
        $this->assertFalse($record->isUpdate());
        $this->assertFalse($record->isDelete());
    }

    public function testIsUpdate()
    {
        $record = new WalChangeRecord('update', 'users', null, 1, 'id', [], [], 'stdClass');

        $this->assertFalse($record->isInsert());
        $this->assertTrue($record->isUpdate());
        $this->assertFalse($record->isDelete());
    }

    public function testIsDelete()
    {
        $record = new WalChangeRecord('delete', 'users', null, 1, 'id', [], [], 'stdClass');

        $this->assertFalse($record->isInsert());
        $this->assertFalse($record->isUpdate());
        $this->assertTrue($record->isDelete());
    }

    // ==================== isFromPartition ====================

    public function testIsFromPartitionTrue()
    {
        $record = new WalChangeRecord('insert', 'orders', 'orders_2024_01', 1, 'id', [], [], 'stdClass');

        $this->assertTrue($record->isFromPartition());
    }

    public function testIsFromPartitionFalse()
    {
        $record = new WalChangeRecord('insert', 'orders', null, 1, 'id', [], [], 'stdClass');

        $this->assertFalse($record->isFromPartition());
    }

    // ==================== Constants ====================

    public function testKindConstants()
    {
        $this->assertSame('insert', WalChangeRecord::KIND_INSERT);
        $this->assertSame('update', WalChangeRecord::KIND_UPDATE);
        $this->assertSame('delete', WalChangeRecord::KIND_DELETE);
    }

    public function testInvalidKindThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid WAL change kind: truncate');

        new WalChangeRecord('truncate', 'users', null, 1, 'id', [], [], 'stdClass');
    }

    // ==================== fromWal2json ====================

    public function testFromWal2jsonInsert()
    {
        $change = [
            'kind'         => 'insert',
            'table'        => 'users',
            'columnnames'  => ['id', 'name', 'email'],
            'columnvalues' => [42, 'John', 'john@example.com'],
        ];

        $record = WalChangeRecord::fromWal2json($change, 'users', 'users', 'id', WalRecordTestModel::class);

        $this->assertNotNull($record);
        $this->assertTrue($record->isInsert());
        $this->assertSame(42, $record->getId());
        $this->assertSame('users', $record->getTable());
        $this->assertNull($record->getPartitionTable());
        $this->assertSame(['id' => 42, 'name' => 'John', 'email' => 'john@example.com'], $record->getColumns());
        $this->assertSame([], $record->getOldColumns());
    }

    public function testFromWal2jsonDelete()
    {
        $change = [
            'kind'    => 'delete',
            'table'   => 'users',
            'oldkeys' => [
                'keynames'  => ['id'],
                'keyvalues' => [15],
            ],
        ];

        $record = WalChangeRecord::fromWal2json($change, 'users', 'users', 'id', 'stdClass');

        $this->assertNotNull($record);
        $this->assertTrue($record->isDelete());
        $this->assertSame(15, $record->getId());
        $this->assertSame(['id' => 15], $record->getOldColumns());
    }

    public function testFromWal2jsonWithPartitionTable()
    {
        $change = [
            'kind'         => 'insert',
            'table'        => 'orders_p0',
            'columnnames'  => ['id', 'name'],
            'columnvalues' => [1, 'test'],
        ];

        $record = WalChangeRecord::fromWal2json($change, 'orders', 'orders_p0', 'id', 'stdClass');

        $this->assertNotNull($record);
        $this->assertSame('orders', $record->getTable());
        $this->assertSame('orders_p0', $record->getPartitionTable());
        $this->assertTrue($record->isFromPartition());
    }

    public function testFromWal2jsonReturnsNullWhenNoKind()
    {
        $change = ['table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [1]];

        $this->assertNull(WalChangeRecord::fromWal2json($change, 'users', 'users', 'id', 'stdClass'));
    }

    public function testFromWal2jsonReturnsNullWhenKeyMissing()
    {
        $change = [
            'kind'         => 'insert',
            'table'        => 'users',
            'columnnames'  => ['name'],
            'columnvalues' => ['John'],
        ];

        $this->assertNull(WalChangeRecord::fromWal2json($change, 'users', 'users', 'id', 'stdClass'));
    }

    public function testFromWal2jsonWithStringUuid()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $change = [
            'kind'         => 'insert',
            'table'        => 'items',
            'columnnames'  => ['uuid', 'name'],
            'columnvalues' => [$uuid, 'Widget'],
        ];

        $record = WalChangeRecord::fromWal2json($change, 'items', 'items', 'uuid', 'stdClass');

        $this->assertNotNull($record);
        $this->assertSame($uuid, $record->getId());
        $this->assertSame('uuid', $record->getKeyName());
    }

    public function testFromWal2jsonDeleteWithMissingOldkeys()
    {
        $change = ['kind' => 'delete', 'table' => 'users'];

        $this->assertNull(WalChangeRecord::fromWal2json($change, 'users', 'users', 'id', 'stdClass'));
    }

    public function testFromWal2jsonWithMismatchedColumnLengths()
    {
        $change = [
            'kind'         => 'insert',
            'table'        => 'items',
            'columnnames'  => ['id', 'name', 'extra'],
            'columnvalues' => [1, 'test'],
        ];

        // columnnames/columnvalues 长度不匹配 → columns 为空 → 无法提取主键 → null
        $this->assertNull(WalChangeRecord::fromWal2json($change, 'items', 'items', 'id', 'stdClass'));
    }

    // ==================== context ====================

    public function testContextSetAndGet()
    {
        $record = new WalChangeRecord('insert', 'users', null, 1, 'id', [], [], 'stdClass');

        $record->setContext('parent_ids', [10, 20, 30]);

        $this->assertSame([10, 20, 30], $record->getContext('parent_ids'));
    }

    public function testContextGetDefaultValue()
    {
        $record = new WalChangeRecord('insert', 'users', null, 1, 'id', [], [], 'stdClass');

        $this->assertNull($record->getContext('nonexistent'));
        $this->assertSame('fallback', $record->getContext('nonexistent', 'fallback'));
    }

    public function testContextIsNotSerialized()
    {
        $record = new WalChangeRecord('insert', 'users', null, 1, 'id', ['id' => 1], [], 'stdClass');
        $record->setContext('temp_data', 'should_not_survive');

        /** @var WalChangeRecord $unserialized */
        $unserialized = unserialize(serialize($record));

        $this->assertNull($unserialized->getContext('temp_data'));
        $this->assertSame(1, $unserialized->getId());
    }

    public function testContextSetReturnsSelf()
    {
        $record = new WalChangeRecord('insert', 'users', null, 1, 'id', [], [], 'stdClass');

        $result = $record->setContext('key', 'value');

        $this->assertSame($record, $result);
    }

    // ==================== makeModel ====================

    public function testMakeModelForInsert()
    {
        $record = new WalChangeRecord(
            'insert',
            'wal_record_test_items',
            null,
            42,
            'id',
            ['id' => 42, 'name' => 'John', 'email' => 'john@example.com'],
            [],
            WalRecordTestModel::class
        );

        $model = $record->makeModel();

        $this->assertInstanceOf(WalRecordTestModel::class, $model);
        $this->assertSame(42, $model->id);
        $this->assertSame('John', $model->name);
        $this->assertSame('john@example.com', $model->email);
        $this->assertTrue($model->exists);
    }

    public function testMakeModelForUpdate()
    {
        $record = new WalChangeRecord(
            'update',
            'wal_record_test_items',
            null,
            7,
            'id',
            ['id' => 7, 'name' => 'Jane', 'email' => 'jane@example.com'],
            [],
            WalRecordTestModel::class
        );

        $model = $record->makeModel();

        $this->assertInstanceOf(WalRecordTestModel::class, $model);
        $this->assertSame(7, $model->id);
        $this->assertSame('Jane', $model->name);
        $this->assertTrue($model->exists);
    }

    public function testMakeModelForDelete()
    {
        $record = new WalChangeRecord(
            'delete',
            'wal_record_test_items',
            null,
            15,
            'id',
            [],
            ['id' => 15],
            WalRecordTestModel::class
        );

        $model = $record->makeModel();

        $this->assertInstanceOf(WalRecordTestModel::class, $model);
        $this->assertSame(15, $model->id);
        $this->assertFalse($model->exists);
    }

    public function testMakeModelForDeleteWithFullReplicaIdentity()
    {
        $record = new WalChangeRecord(
            'delete',
            'wal_record_test_items',
            null,
            15,
            'id',
            [],
            ['id' => 15, 'name' => 'OldName', 'email' => 'old@example.com'],
            WalRecordTestModel::class
        );

        $model = $record->makeModel();

        $this->assertSame(15, $model->id);
        $this->assertSame('OldName', $model->name);
        $this->assertSame('old@example.com', $model->email);
        $this->assertFalse($model->exists);
    }

    public function testMakeModelSyncsOriginal()
    {
        $record = new WalChangeRecord(
            'update',
            'wal_record_test_items',
            null,
            1,
            'id',
            ['id' => 1, 'name' => 'Test'],
            [],
            WalRecordTestModel::class
        );

        $model = $record->makeModel();

        // After syncOriginal, getDirty should be empty
        $this->assertEmpty($model->getDirty());
    }

    // ==================== jsonSerialize ====================

    public function testJsonSerialize()
    {
        $record = new WalChangeRecord(
            'insert',
            'users',
            'users_2024',
            42,
            'id',
            ['id' => 42, 'name' => 'John'],
            [],
            WalRecordTestModel::class
        );

        $json = $record->jsonSerialize();

        $this->assertSame('insert', $json['kind']);
        $this->assertSame('users', $json['table']);
        $this->assertSame('users_2024', $json['partitionTable']);
        $this->assertSame(42, $json['id']);
        $this->assertSame('id', $json['keyName']);
        $this->assertSame(['id' => 42, 'name' => 'John'], $json['columns']);
        $this->assertSame([], $json['oldColumns']);
        $this->assertSame(WalRecordTestModel::class, $json['modelClass']);
    }

    public function testJsonEncodeProducesValidJson()
    {
        $record = new WalChangeRecord('update', 'orders', null, 7, 'id', ['id' => 7, 'status' => 'paid'], ['id' => 7, 'status' => 'pending'], 'stdClass');

        $encoded = json_encode($record);
        $this->assertNotFalse($encoded);

        $decoded = json_decode($encoded, true);
        $this->assertSame('update', $decoded['kind']);
        $this->assertSame(7, $decoded['id']);
        $this->assertNull($decoded['partitionTable']);
    }

    public function testJsonSerializeExcludesContext()
    {
        $record = new WalChangeRecord('insert', 'users', null, 1, 'id', ['id' => 1], [], 'stdClass');
        $record->setContext('temp', 'value');

        $json = $record->jsonSerialize();

        $this->assertArrayNotHasKey('context', $json);
    }

    public function testMakeModelSetsCorrectPrimaryKey()
    {
        $record = new WalChangeRecord(
            'insert',
            'wal_record_test_items',
            null,
            99,
            'id',
            ['id' => 99, 'name' => 'Test'],
            [],
            WalRecordTestModel::class
        );

        $model = $record->makeModel();

        $this->assertSame(99, $model->getKey());
    }
}

/**
 * @property int    $id
 * @property string $name
 * @property string $email
 */
class WalRecordTestModel extends Model
{
    protected $table = 'wal_record_test_items';

    protected $fillable = ['id', 'name', 'email'];
}
