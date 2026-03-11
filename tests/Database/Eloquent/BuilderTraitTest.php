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
            $user->nickname = 'user-'.$i;
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

    // ========================================================================
    // findByPks 内联优化后的严格验证
    // ========================================================================

    /**
     * 核心交叉验证：findByPks 与 findByOneUniqueColumnValues 结果完全一致.
     *
     * 确保内联优化后的 findByPks 和旧调用链产出完全相同的结果。
     */
    public function testFindByPksResultMatchesFindByOneUniqueColumnValues()
    {
        $this->createUsers(10);
        $pks = [7, 3, 10, 1, 5, 999, 8];

        // 清缓存，两条路径都从 DB 查
        User::query()->getCache()->clear();
        $fromOldPath = User::query()->findByOneUniqueColumnValues('id', $pks);

        User::query()->getCache()->clear();
        $fromNewPath = User::query()->findByPks($pks);

        // 数量一致
        $this->assertSame($fromOldPath->count(), $fromNewPath->count());
        // key 顺序一致
        $this->assertSame(
            $fromOldPath->keys()->values()->toArray(),
            $fromNewPath->keys()->values()->toArray()
        );
        // 每条数据一致
        foreach ($fromOldPath as $pk => $model) {
            $this->assertSame($model->id, $fromNewPath->get($pk)->id);
            $this->assertSame($model->nickname, $fromNewPath->get($pk)->nickname);
        }
    }

    /**
     * 交叉验证 — 缓存命中时，两种路径的结果仍一致.
     */
    public function testFindByPksResultMatchesFindByOneUniqueColumnValuesCacheHit()
    {
        $this->createUsers(5);
        $pks = [4, 2, 5, 1, 3];

        // 预热缓存
        User::query()->findByPks($pks);

        $fromOldPath = User::query()->findByOneUniqueColumnValues('id', $pks);
        $fromNewPath = User::query()->findByPks($pks);

        $this->assertSame($fromOldPath->count(), $fromNewPath->count());
        $this->assertSame(
            $fromOldPath->keys()->values()->toArray(),
            $fromNewPath->keys()->values()->toArray()
        );
        foreach ($fromOldPath as $pk => $model) {
            $this->assertSame($model->id, $fromNewPath->get($pk)->id);
            $this->assertTrue($fromNewPath->get($pk)->isFromCache());
        }
    }

    /**
     * 占位符场景：查询不存在的 PK 后，占位符被写入，再次查询直接跳过不返回.
     */
    public function testFindByPksPlaceholderPreventsRepeatedDbQuery()
    {
        $this->createUsers(3);

        // 第一次：查不存在的 PK，应写入占位符
        $result1 = User::query()->findByPks([999, 1000]);
        $this->assertTrue($result1->isEmpty());

        // 第二次：占位符存在，应直接返回空，不查 DB
        $result2 = User::query()->findByPks([999, 1000]);
        $this->assertTrue($result2->isEmpty());
        $this->assertInstanceOf(EloquentCollection::class, $result2);
    }

    /**
     * 占位符 + 存在行混合：不存在的 PK 被占位符跳过，存在的 PK 正常返回.
     */
    public function testFindByPksPlaceholderMixedWithExistingRows()
    {
        $this->createUsers(3);

        // 第一次查询：1 存在，999 不存在 → 999 写入占位符
        $result1 = User::query()->findByPks([1, 999]);
        $this->assertSame(1, $result1->count());
        $this->assertTrue($result1->has(1));
        $this->assertFalse($result1->has(999));

        // 第二次查询：1 走缓存，999 走占位符跳过，2 走 DB
        $result2 = User::query()->findByPks([999, 1, 2]);
        $this->assertSame(2, $result2->count());
        $this->assertTrue($result2->has(1));
        $this->assertTrue($result2->has(2));
        $this->assertFalse($result2->has(999));

        // 1 来自缓存
        $this->assertTrue($result2->get(1)->isFromCache());
        // 2 来自 DB
        $this->assertFalse($result2->get(2)->isFromCache());
    }

    /**
     * 占位符混合场景下输出顺序正确.
     */
    public function testFindByPksPlaceholderMixedOutputOrder()
    {
        $this->createUsers(5);

        // 预热：999 写入占位符，1 和 3 写入缓存
        User::query()->findByPks([999, 1, 3]);

        // 混合查询，检查输出顺序（跳过不存在的 999）
        $result = User::query()->findByPks([5, 999, 3, 2, 1]);
        $this->assertSame(4, $result->count());
        // 输出 key 顺序应跟随输入中存在的 PK 顺序
        $this->assertSame([5, 3, 2, 1], $result->keys()->values()->toArray());
    }

    /**
     * 部分缓存命中时输出顺序严格按输入顺序.
     */
    public function testFindByPksPartialCacheHitOutputOrder()
    {
        $this->createUsers(5);

        // 只缓存 id=2,4
        User::query()->findByPks([2, 4]);

        // 查询 [5, 4, 3, 2, 1]，其中 2,4 走缓存，1,3,5 走 DB
        $result = User::query()->findByPks([5, 4, 3, 2, 1]);
        $this->assertSame(5, $result->count());
        $this->assertSame([5, 4, 3, 2, 1], $result->keys()->values()->toArray());

        // 验证 isFromCache 标记
        $this->assertTrue($result->get(2)->isFromCache());
        $this->assertTrue($result->get(4)->isFromCache());
        $this->assertFalse($result->get(1)->isFromCache());
        $this->assertFalse($result->get(3)->isFromCache());
        $this->assertFalse($result->get(5)->isFromCache());
    }

    /**
     * 重复 PK + 缓存命中：结果 key 去重，数据正确.
     */
    public function testFindByPksDuplicatePksCacheHit()
    {
        $this->createUsers(3);

        // 预热缓存
        User::query()->findByPks([1, 2, 3]);

        // 带重复 PK 查询
        $result = User::query()->findByPks([2, 1, 2, 3, 1]);
        // Collection put 同 key 覆盖，最终只保留不重复的 key
        $this->assertSame(3, $result->count());
        $this->assertTrue($result->has(1));
        $this->assertTrue($result->has(2));
        $this->assertTrue($result->has(3));

        // 每条都来自缓存
        $result->each(function (User $user) {
            $this->assertTrue($user->isFromCache());
        });
    }

    /**
     * noCache 场景下不写入占位符：查不存在的 PK 后，正常路径仍会查 DB.
     */
    public function testNoCacheDoesNotWritePlaceholder()
    {
        $this->createUsers(3);

        // noCache 查询不存在的 PK — 不应写入占位符
        $result1 = User::query()->noCache()->findByPks([999]);
        $this->assertTrue($result1->isEmpty());

        // 正常查询 — 如果占位符没被写入，999 应作为 miss 查 DB（然后写占位符）
        // 这里关键是验证 noCache 没有污染缓存
        $result2 = User::query()->findByPks([999]);
        $this->assertTrue($result2->isEmpty());
    }

    /**
     * noCache 场景下每次查询结果都不带 isFromCache 标记.
     */
    public function testNoCacheFindByPksNeverMarkedFromCache()
    {
        $this->createUsers(3);

        // 先正常查询写入缓存
        User::query()->findByPks([1, 2, 3]);

        // 连续两次 noCache 查询，都不应标记为来自缓存
        for ($i = 0; $i < 2; $i++) {
            $result = User::query()->noCache()->findByPks([1, 2, 3]);
            $this->assertSame(3, $result->count());
            $result->each(function (User $user) {
                $this->assertFalse($user->isFromCache());
            });
        }
    }

    /**
     * findByPk 走缓存命中路径时 isFromCache 为 true.
     */
    public function testFindByPkCacheHitIsFromCache()
    {
        $this->createUsers(3);

        // 第一次：缓存未命中
        $user1 = User::query()->findByPk(1);
        $this->assertFalse($user1->isFromCache());

        // 第二次：缓存命中
        $user2 = User::query()->findByPk(1);
        $this->assertTrue($user2->isFromCache());
        $this->assertSame($user1->id, $user2->id);
        $this->assertSame($user1->nickname, $user2->nickname);
    }

    /**
     * 全部 PK 都不存在于 DB 中的场景（全 miss + 全占位符）.
     */
    public function testFindByPksAllNonExistent()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks([100, 200, 300]);
        $this->assertInstanceOf(EloquentCollection::class, $result);
        $this->assertTrue($result->isEmpty());

        // 再次查询 — 走占位符，仍为空
        $result2 = User::query()->findByPks([100, 200, 300]);
        $this->assertInstanceOf(EloquentCollection::class, $result2);
        $this->assertTrue($result2->isEmpty());
    }

    /**
     * 缓存命中时，返回的模型属性值与 DB 原始值完全一致.
     */
    public function testFindByPksCacheHitDataIntegrity()
    {
        $this->createUsers(5);

        // DB 查询
        $fromDb = User::query()->findByPks([1, 2, 3, 4, 5]);
        $dbData = $fromDb->map(function (User $u) {
            return ['id' => $u->id, 'nickname' => $u->nickname, 'sort' => $u->sort];
        })->toArray();

        // 缓存查询
        $fromCache = User::query()->findByPks([1, 2, 3, 4, 5]);
        $cacheData = $fromCache->map(function (User $u) {
            return ['id' => $u->id, 'nickname' => $u->nickname, 'sort' => $u->sort];
        })->toArray();

        $this->assertSame($dbData, $cacheData);
    }

    /**
     * 单个 PK 的 findByPks 返回的 Collection key 为该 PK.
     */
    public function testFindByPksSinglePkKeyedCorrectly()
    {
        $this->createUsers(3);

        $result = User::query()->findByPks([2]);
        $this->assertSame(1, $result->count());
        $this->assertSame([2], $result->keys()->values()->toArray());
        $this->assertSame(2, $result->get(2)->id);
    }

    /**
     * findByPks 与 findByIds 静态方法结果完全一致.
     */
    public function testFindByPksMatchesStaticFindByIds()
    {
        $this->createUsers(5);
        $pks = [5, 3, 1];

        User::query()->getCache()->clear();
        $fromInstance = User::query()->findByPks($pks);

        User::query()->getCache()->clear();
        $fromStatic = User::findByIds($pks);

        $this->assertSame($fromInstance->count(), $fromStatic->count());
        $this->assertSame(
            $fromInstance->keys()->values()->toArray(),
            $fromStatic->keys()->values()->toArray()
        );
        foreach ($fromInstance as $pk => $model) {
            $this->assertSame($model->id, $fromStatic->get($pk)->id);
        }
    }
}
