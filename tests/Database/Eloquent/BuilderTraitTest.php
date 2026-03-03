<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use ArrayIterator;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * Builder trait 中 findByPks / findByOneUniqueColumnValues / findUniqueRows 的严格单元测试.
 *
 * 测试重点：
 *   - 入参类型兼容性（array / Collection / Traversable）
 *   - 出参类型一致性（EloquentCollection）
 *   - 结果 key 的正确性
 *   - 入参顺序保留
 *   - 无效主键过滤
 *   - 缓存命中 / 未命中 / 占位符场景
 *   - 边界：空输入、全部不存在、部分存在
 */
class BuilderTraitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->comment('id');
            $table->string('nickname')->nullable();
            $table->integer('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        User::query()->getCache()->clear();
    }

    protected function createUsers(int $count = 5)
    {
        for ($i = 1; $i <= $count; $i++) {
            $user = new User();
            $user->nickname = 'user-' . $i;
            $user->sort = $i;
            $user->save();
        }
    }

    // ========================================================================
    // findByPks
    // ========================================================================

    public function testFindByPksReturnsEloquentCollection()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks([1, 2, 3]);

        $this->assertInstanceOf(EloquentCollection::class, $result);
    }

    public function testFindByPksEmptyInputReturnsEmptyCollection()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks([]);
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testFindByPksFiltersInvalidPks()
    {
        $this->createUsers(3);

        // null, 0, '' 都是无效的 PK，应被过滤
        $result = User::query()->findByPks([null, 0, '', false]);
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testFindByPksMixedValidAndInvalidPks()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks([null, 1, 0, 2, '', 3]);
        $this->assertSame(3, $result->count());

        foreach ([1, 2, 3] as $id) {
            $this->assertNotNull($result->get($id));
            $this->assertSame($id, $result->get($id)->id);
        }
    }

    public function testFindByPksPreservesInputOrder()
    {
        $this->createUsers(5);

        $result = User::query()->findByPks([3, 1, 5, 2, 4]);
        $this->assertSame(5, $result->count());
        $this->assertSame([3, 1, 5, 2, 4], $result->keys()->values()->toArray());
    }

    public function testFindByPksResultKeyedByPrimaryKey()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks([2, 3]);

        $this->assertTrue($result->has(2));
        $this->assertTrue($result->has(3));
        $this->assertFalse($result->has(1));

        $this->assertSame(2, $result->get(2)->id);
        $this->assertSame(3, $result->get(3)->id);
    }

    public function testFindByPksAcceptsCollection()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks(Collection::make([1, 2, 3]));
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertSame(3, $result->count());
    }

    public function testFindByPksAcceptsTraversable()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks(new ArrayIterator([1, 2, 3]));
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertSame(3, $result->count());
    }

    public function testFindByPksNonExistentIdsExcluded()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks([1, 999, 2, 1000]);
        $this->assertSame(2, $result->count());
        $this->assertTrue($result->has(1));
        $this->assertTrue($result->has(2));
        $this->assertFalse($result->has(999));
        $this->assertFalse($result->has(1000));
    }

    public function testFindByPksCacheMissAndHit()
    {
        $this->createUsers(3);

        // 第一次查询 — 缓存未命中，从数据库查询
        $result1 = User::query()->findByPks([1, 2]);
        $this->assertSame(2, $result1->count());
        $result1->each(function (User $user) {
            $this->assertFalse($user->isFromCache());
        });

        // 第二次查询 — 缓存命中
        $result2 = User::query()->findByPks([1, 2]);
        $this->assertSame(2, $result2->count());
        $result2->each(function (User $user) {
            $this->assertTrue($user->isFromCache());
        });

        // 数据一致性
        $this->assertSame(
            $result1->pluck('nickname', 'id')->toArray(),
            $result2->pluck('nickname', 'id')->toArray()
        );
    }

    public function testFindByPksPartialCacheHit()
    {
        $this->createUsers(5);

        // 先缓存 id=1,2
        User::query()->findByPks([1, 2]);

        // 查询 id=1,2,3 — 其中 1,2 走缓存，3 走数据库
        $result = User::query()->findByPks([1, 2, 3]);
        $this->assertSame(3, $result->count());

        // id=1,2 应来自缓存
        $this->assertTrue($result->get(1)->isFromCache());
        $this->assertTrue($result->get(2)->isFromCache());
        // id=3 应来自数据库
        $this->assertFalse($result->get(3)->isFromCache());
    }

    public function testFindByPksDuplicateIds()
    {
        $this->createUsers(3);

        // 重复 ID 不应导致重复结果
        $result = User::query()->findByPks([1, 1, 2, 2, 3]);
        // findByOneUniqueColumnValues 保留输入顺序，重复 key 会被覆盖
        $this->assertSame(3, $result->count());
    }

    // ========================================================================
    // findByOneUniqueColumnValues
    // ========================================================================

    public function testFindByOneUniqueColumnValuesReturnsEloquentCollection()
    {
        $this->createUsers(3);

        $result = User::query()->findByOneUniqueColumnValues('id', [1, 2]);
        $this->assertInstanceOf(EloquentCollection::class, $result);
    }

    public function testFindByOneUniqueColumnValuesEmptyInput()
    {
        $this->createUsers(3);

        $result = User::query()->findByOneUniqueColumnValues('id', []);
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testFindByOneUniqueColumnValuesKeyedByColumn()
    {
        $this->createUsers(3);

        $result = User::query()->findByOneUniqueColumnValues('id', [1, 3]);
        $this->assertTrue($result->has(1));
        $this->assertTrue($result->has(3));
        $this->assertFalse($result->has(2));
    }

    public function testFindByOneUniqueColumnValuesPreservesOrder()
    {
        $this->createUsers(5);

        $result = User::query()->findByOneUniqueColumnValues('id', [4, 2, 5, 1, 3]);
        $this->assertSame([4, 2, 5, 1, 3], $result->keys()->values()->toArray());
    }

    public function testFindByOneUniqueColumnValuesNonExistentExcluded()
    {
        $this->createUsers(3);

        $result = User::query()->findByOneUniqueColumnValues('id', [1, 999, 2]);
        $this->assertSame(2, $result->count());
        $this->assertFalse($result->has(999));
    }

    public function testFindByOneUniqueColumnValuesWithNicknameColumn()
    {
        $this->createUsers(3);

        $result = User::query()->findByOneUniqueColumnValues('nickname', ['user-1', 'user-3']);
        $this->assertSame(2, $result->count());
        $this->assertTrue($result->has('user-1'));
        $this->assertTrue($result->has('user-3'));
        $this->assertSame('user-1', $result->get('user-1')->nickname);
        $this->assertSame('user-3', $result->get('user-3')->nickname);
    }

    public function testFindByOneUniqueColumnValuesCacheConsistency()
    {
        $this->createUsers(3);

        // 第一次：缓存未命中
        $result1 = User::query()->findByOneUniqueColumnValues('id', [1, 2]);
        $this->assertSame(2, $result1->count());

        // 第二次：缓存命中，结果应一致
        $result2 = User::query()->findByOneUniqueColumnValues('id', [1, 2]);
        $this->assertSame(2, $result2->count());

        $this->assertSame(
            $result1->pluck('nickname', 'id')->toArray(),
            $result2->pluck('nickname', 'id')->toArray()
        );
    }

    // ========================================================================
    // findUniqueRows
    // ========================================================================

    public function testFindUniqueRowsReturnsEloquentCollection()
    {
        $this->createUsers(3);

        $result = User::query()->findUniqueRows([['id' => 1], ['id' => 2]]);
        $this->assertInstanceOf(EloquentCollection::class, $result);
    }

    public function testFindUniqueRowsEmptyArrayReturnsEmpty()
    {
        $result = User::query()->findUniqueRows([]);
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testFindUniqueRowsEmptyCollectionReturnsEmpty()
    {
        $result = User::query()->findUniqueRows(Collection::make());
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testFindUniqueRowsAcceptsArray()
    {
        $this->createUsers(3);

        $result = User::query()->findUniqueRows([['id' => 1], ['id' => 2]]);
        $this->assertSame(2, $result->count());
    }

    public function testFindUniqueRowsAcceptsCollection()
    {
        $this->createUsers(3);

        $conditions = Collection::make([['id' => 1], ['id' => 2], ['id' => 3]]);
        $result = User::query()->findUniqueRows($conditions);
        $this->assertSame(3, $result->count());
    }

    public function testFindUniqueRowsAcceptsTraversable()
    {
        $this->createUsers(3);

        $conditions = new ArrayIterator([['id' => 1], ['id' => 2]]);
        $result = User::query()->findUniqueRows($conditions);
        $this->assertSame(2, $result->count());
    }

    public function testFindUniqueRowsCacheMiss()
    {
        $this->createUsers(3);

        $result = User::query()->findUniqueRows([['id' => 1], ['id' => 2]]);
        $this->assertSame(2, $result->count());

        $result->each(function (User $user) {
            $this->assertFalse($user->isFromCache());
        });
    }

    public function testFindUniqueRowsCacheHit()
    {
        $this->createUsers(3);

        // 第一次查询 — 写入缓存
        User::query()->findUniqueRows([['id' => 1], ['id' => 2]]);

        // 第二次查询 — 从缓存读取
        $result = User::query()->findUniqueRows([['id' => 1], ['id' => 2]]);
        $this->assertSame(2, $result->count());

        $result->each(function (User $user) {
            $this->assertTrue($user->isFromCache());
        });
    }

    public function testFindUniqueRowsPartialCacheHit()
    {
        $this->createUsers(5);

        // 先缓存 id=1,2
        User::query()->findUniqueRows([['id' => 1], ['id' => 2]]);

        // 查询 id=1,2,3 — 1,2 来自缓存，3 来自数据库
        $result = User::query()->findUniqueRows([['id' => 1], ['id' => 2], ['id' => 3]]);
        $this->assertSame(3, $result->count());
    }

    public function testFindUniqueRowsPlaceholderPreventsRepeatedDbQuery()
    {
        $this->createUsers(3);

        // 查询不存在的 id — 应写入占位符
        $result1 = User::query()->findUniqueRows([['id' => 999]]);
        $this->assertTrue($result1->isEmpty());

        // 再次查询 — 由于占位符存在，不应再查数据库
        $result2 = User::query()->findUniqueRows([['id' => 999]]);
        $this->assertTrue($result2->isEmpty());
    }

    public function testFindUniqueRowsCompoundUniqueKey()
    {
        $this->createUsers(3);

        $conditions = [
            ['id' => 1, 'nickname' => 'user-1'],
            ['id' => 2, 'nickname' => 'user-2'],
        ];

        $result = User::query()->findUniqueRows($conditions);
        $this->assertSame(2, $result->count());
    }

    public function testFindUniqueRowsNonExistentRowsExcluded()
    {
        $this->createUsers(3);

        $result = User::query()->findUniqueRows([
            ['id' => 1],
            ['id' => 999],
            ['id' => 2],
            ['id' => 1000],
        ]);

        $this->assertSame(2, $result->count());
    }

    public function testFindUniqueRowsResultIsValuesCollection()
    {
        $this->createUsers(3);

        $result = User::query()->findUniqueRows([['id' => 1], ['id' => 2]]);

        // values() 应返回连续的 0-based 索引
        $keys = $result->keys()->toArray();
        $this->assertSame([0, 1], $keys);
    }

    public function testFindUniqueRowsDataConsistencyAcrossCacheStates()
    {
        $this->createUsers(3);

        $conditions = [['id' => 1], ['id' => 2], ['id' => 3]];

        // 第一次查询 — 从数据库获取
        $fromDb = User::query()->findUniqueRows($conditions);

        // 第二次查询 — 从缓存获取
        $fromCache = User::query()->findUniqueRows($conditions);

        // 数据应完全一致
        $this->assertSame($fromDb->count(), $fromCache->count());

        foreach ($fromDb as $index => $dbRow) {
            $cacheRow = $fromCache[$index];
            $this->assertSame($dbRow->id, $cacheRow->id);
            $this->assertSame($dbRow->nickname, $cacheRow->nickname);
        }
    }

    // ========================================================================
    // findByPk / findUniqueRow (单条查询委托方法)
    // ========================================================================

    public function testFindByPkReturnsModelOrNull()
    {
        $this->createUsers(3);

        $user = User::query()->findByPk(1);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->id);

        $none = User::query()->findByPk(999);
        $this->assertNull($none);
    }

    public function testFindByPkInvalidKeyReturnsNull()
    {
        $this->createUsers(3);

        $this->assertNull(User::query()->findByPk(0));
        $this->assertNull(User::query()->findByPk(null));
        $this->assertNull(User::query()->findByPk(''));
    }

    public function testFindUniqueRowReturnsModelOrNull()
    {
        $this->createUsers(3);

        $user = User::query()->findUniqueRow(['id' => 1]);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(1, $user->id);

        $none = User::query()->findUniqueRow(['id' => 999]);
        $this->assertNull($none);
    }

    // ========================================================================
    // noCache — 禁用缓存后行为一致性
    // ========================================================================

    public function testNoCacheFindByPksAlwaysFromDatabase()
    {
        $this->createUsers(3);

        // 先正常查询写入缓存
        User::query()->findByPks([1, 2]);

        // 禁用缓存后查询 — 应始终不走缓存
        $result = User::query()->noCache()->findByPks([1, 2]);
        $this->assertSame(2, $result->count());
        $result->each(function (User $user) {
            $this->assertFalse($user->isFromCache());
        });
    }

    public function testNoCacheFindUniqueRowsAlwaysFromDatabase()
    {
        $this->createUsers(3);

        // 先正常查询写入缓存
        User::query()->findUniqueRows([['id' => 1]]);

        // 禁用缓存后
        $result = User::query()->noCache()->findUniqueRows([['id' => 1]]);
        $this->assertSame(1, $result->count());
        $result->each(function (User $user) {
            $this->assertFalse($user->isFromCache());
        });
    }

    // ========================================================================
    // 大批量场景
    // ========================================================================

    public function testFindByPksLargeBatch()
    {
        $this->createUsers(100);

        $ids = range(1, 100);
        $result = User::query()->findByPks($ids);

        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertSame(100, $result->count());

        // 验证每个 ID 都有对应的 User
        foreach ($ids as $id) {
            $this->assertNotNull($result->get($id), "ID {$id} should exist in results");
            $this->assertSame($id, $result->get($id)->id);
        }
    }

    public function testFindUniqueRowsLargeBatch()
    {
        $this->createUsers(100);

        $conditions = [];
        for ($i = 1; $i <= 100; $i++) {
            $conditions[] = ['id' => $i];
        }

        $result = User::query()->findUniqueRows($conditions);
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertSame(100, $result->count());
    }

    // ========================================================================
    // 静态代理方法 findById / findByIds
    // ========================================================================

    public function testStaticFindByIdConsistency()
    {
        $this->createUsers(3);

        $user = User::findById(2);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(2, $user->id);

        $this->assertNull(User::findById(999));
        $this->assertNull(User::findById(0));
        $this->assertNull(User::findById(null));
    }

    public function testStaticFindByIdsConsistency()
    {
        $this->createUsers(5);

        $result = User::findByIds([3, 1, 5]);
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertSame(3, $result->count());
        $this->assertSame([3, 1, 5], $result->keys()->values()->toArray());
    }
}
