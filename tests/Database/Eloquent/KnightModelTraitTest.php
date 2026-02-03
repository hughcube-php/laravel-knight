<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Database\Eloquent\Builder as KnightBuilder;
use HughCube\Laravel\Knight\Database\Eloquent\Collection as KnightCollection;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\KnightModelTrait;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class KnightModelTraitTestModel extends EloquentModel
{
    use KnightModelTrait;

    protected $table = 'knight_model_trait_users';

    public $timestamps = false;

    protected $guarded = [];

    public function publicConvertEmptyStringsToNull($value)
    {
        return $this->convertEmptyStringsToNull($value);
    }
}

class KnightModelTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('knight_model_trait_users');
        Schema::create('knight_model_trait_users', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->string('name')->nullable();
        });
    }

    public function testQueryUsesKnightBuilder()
    {
        $builder = KnightModelTraitTestModel::query();

        $this->assertInstanceOf(KnightBuilder::class, $builder);
        $this->assertSame($builder, $builder->noCache());
    }

    public function testNewCollectionReturnsKnightCollection()
    {
        $model = new KnightModelTraitTestModel();
        $collection = $model->newCollection();

        $this->assertInstanceOf(KnightCollection::class, $collection);
    }

    public function testIfAvailableReturnSelfUsesTrait()
    {
        $model = new KnightModelTraitTestModel();

        $this->assertSame($model, $model->ifAvailableReturnSelf());

        $model->setAttribute('deleted_at', '2024-01-01 00:00:00');
        $this->assertNull($model->ifAvailableReturnSelf());
    }

    public function testConvertEmptyStringsToNull()
    {
        $model = new KnightModelTraitTestModel();

        $this->assertNull($model->publicConvertEmptyStringsToNull(''));
        $this->assertNull($model->publicConvertEmptyStringsToNull(null));

        $this->assertSame('hello', $model->publicConvertEmptyStringsToNull('hello'));
        $this->assertSame(' ', $model->publicConvertEmptyStringsToNull(' '));
        $this->assertSame('0', $model->publicConvertEmptyStringsToNull('0'));
        $this->assertSame(0, $model->publicConvertEmptyStringsToNull(0));
        $this->assertSame(false, $model->publicConvertEmptyStringsToNull(false));
        $this->assertSame([], $model->publicConvertEmptyStringsToNull([]));
    }
}
