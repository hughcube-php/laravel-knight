<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent\Traits;

use HughCube\Laravel\Knight\Database\Eloquent\Traits\Auditable;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AuditableTestModel extends Model
{
    use Auditable;

    protected $table = 'auditable_test';
    protected $guarded = [];
    public $timestamps = false;
}

class AuditableFilteredModel extends Model
{
    use Auditable;

    protected $table = 'auditable_test';
    protected $guarded = [];
    public $timestamps = false;

    public function getAuditableColumns(): ?array
    {
        return ['name'];
    }
}

class AuditableExcludedModel extends Model
{
    use Auditable;

    protected $table = 'auditable_test';
    protected $guarded = [];
    public $timestamps = false;

    public function getAuditExcludedColumns(): array
    {
        return ['email'];
    }
}

class AuditableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('auditable_test', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default('');
            $table->string('email')->default('');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('auditable_test');
        parent::tearDown();
    }

    public function testAuditRecordsChangesOnUpdate()
    {
        $model = AuditableTestModel::create(['name' => 'foo', 'email' => 'foo@bar.com']);
        $model->name = 'bar';
        $model->save();

        $changes = $model->getAuditChanges();
        $this->assertCount(1, $changes);
        $this->assertSame('name', $changes[0]['column']);
        $this->assertSame('foo', $changes[0]['old']);
        $this->assertSame('bar', $changes[0]['new']);
    }

    public function testAuditableColumnsFilter()
    {
        $model = AuditableFilteredModel::create(['name' => 'foo', 'email' => 'foo@bar.com']);
        $model->name = 'bar';
        $model->email = 'bar@baz.com';
        $model->save();

        $changes = $model->getAuditChanges();
        $this->assertCount(1, $changes);
        $this->assertSame('name', $changes[0]['column']);
    }

    public function testExcludedColumnsFilter()
    {
        $model = AuditableExcludedModel::create(['name' => 'foo', 'email' => 'foo@bar.com']);
        $model->name = 'bar';
        $model->email = 'bar@baz.com';
        $model->save();

        $changes = $model->getAuditChanges();
        $this->assertCount(1, $changes);
        $this->assertSame('name', $changes[0]['column']);
    }

    public function testDisableAudit()
    {
        $model = AuditableTestModel::create(['name' => 'foo', 'email' => 'foo@bar.com']);
        $model->disableAudit();

        $this->assertFalse($model->isAuditEnabled());

        $model->name = 'bar';
        $model->save();

        $this->assertEmpty($model->getAuditChanges());
    }

    public function testEnableAudit()
    {
        $model = AuditableTestModel::create(['name' => 'foo', 'email' => 'foo@bar.com']);
        $model->disableAudit();
        $model->enableAudit();

        $this->assertTrue($model->isAuditEnabled());

        $model->name = 'bar';
        $model->save();

        $this->assertCount(1, $model->getAuditChanges());
    }

    public function testNoChangesNoAuditLog()
    {
        $model = AuditableTestModel::create(['name' => 'foo', 'email' => 'foo@bar.com']);
        $model->save(); // no changes

        $this->assertEmpty($model->getAuditChanges());
    }
}
