<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/5
 * Time: 2:49 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BuilderTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->comment('id');
            $table->string('nickname')->nullable();
            $table->integer('range')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testWhereLikeMatchesPattern()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s%%', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->whereLike('nickname', sprintf('%%%s%%', $keyword))->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereLikeDoesNotAutoAddWildcards()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s%%', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->whereLike('nickname', $keyword)->first();
        $this->assertNull($user);
    }

    public function testWhereEscapeLikeMatchesEscapedValue()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s%%', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->whereEscapeLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereRawLikeMatchesAny()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s%%', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->whereRaw("nickname LIKE '%%%'")->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereLeftLikeMatchesPrefix()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->whereLeftLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereEscapeLeftLikeMatchesEscapedPrefix()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->whereEscapeLeftLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereRightLikeMatchesSuffix()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', Str::random(), $keyword);
        $user->save();

        /** @var User $user */
        $user = User::query()->whereRightLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereEscapeRightLikeMatchesEscapedSuffix()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', Str::random(), $keyword);
        $user->save();

        /** @var User $user */
        $user = User::query()->whereEscapeRightLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereLikeMatchesPattern()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()
            ->where('id', 0)
            ->orWhereLike('nickname', sprintf('%%%s%%', $keyword))
            ->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereLikeDoesNotAutoAddWildcards()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()
            ->where('id', 0)
            ->orWhereLike('nickname', $keyword)
            ->first();
        $this->assertNull($user);
    }

    public function testOrWhereEscapeLikeMatchesEscapedValue()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()
            ->where('id', 0)
            ->orWhereEscapeLike('nickname', $keyword)
            ->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereLeftLikeMatchesPrefix()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()
            ->where('id', 0)
            ->orWhereLeftLike('nickname', $keyword)
            ->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereEscapeLeftLikeMatchesEscapedPrefix()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()
            ->where('id', 0)
            ->orWhereEscapeLeftLike('nickname', $keyword)
            ->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereRightLikeMatchesSuffix()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', Str::random(), $keyword);
        $user->save();

        /** @var User $user */
        $user = User::query()
            ->where('id', 0)
            ->orWhereRightLike('nickname', $keyword)
            ->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereEscapeRightLikeMatchesEscapedSuffix()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', Str::random(), $keyword);
        $user->save();

        /** @var User $user */
        $user = User::query()
            ->where('id', 0)
            ->orWhereEscapeRightLike('nickname', $keyword)
            ->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereRange()
    {
        $keyword = __FUNCTION__;

        for ($i = 1; $i <= 100; $i++) {
            $user = new User();
            $user->nickname = sprintf('%s_%s', $keyword, Str::random());
            $user->range = $i;
            $user->save();
        }

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s_%%'", $keyword))
            ->whereRange('range', [1, 10])
            ->get();
        $this->assertSame(
            range(1, 10),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->whereRange('range', [11, 20])
            ->get();
        $this->assertSame(
            range(11, 20),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->whereRange('range', [null, 20])
            ->get();
        $this->assertSame(
            range(1, 20),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->whereRange('range', [20, null])
            ->get();
        $this->assertSame(
            range(20, 100),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );
    }

    public function testOrWhereRange()
    {
        $keyword = __FUNCTION__;

        for ($i = 1; $i <= 100; $i++) {
            $user = new User();
            $user->nickname = sprintf('%s_%s', $keyword, Str::random());
            $user->range = $i;
            $user->save();
        }

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s_%%'", $keyword))
            ->orWhereRange('range', [1, 10])
            ->get();
        $this->assertSame(
            range(1, 100),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->orWhereRange('range', [11, 20])
            ->get();
        $this->assertSame(
            range(1, 100),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );
    }

    public function testWhereNotRange()
    {
        $keyword = __FUNCTION__;

        for ($i = 1; $i <= 100; $i++) {
            $user = new User();
            $user->nickname = sprintf('%s_%s', $keyword, Str::random());
            $user->range = $i;
            $user->save();
        }

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s_%%'", $keyword))
            ->whereNotRange('range', [1, 10])
            ->get();
        $this->assertSame(
            range(11, 100),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->whereNotRange('range', [91, 100])
            ->get();
        $this->assertSame(
            range(1, 90),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );
    }

    public function testOrWhereNotRange()
    {
        $keyword = __FUNCTION__;

        for ($i = 1; $i <= 100; $i++) {
            $user = new User();
            $user->nickname = sprintf('%s_%s', $keyword, Str::random());
            $user->range = $i;
            $user->save();
        }

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s_%%'", $keyword))
            ->orWhereNotRange('range', [1, 10])
            ->get();
        $this->assertSame(
            range(1, 100),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->orWhereNotRange('range', [91, 100])
            ->get();
        $this->assertSame(
            range(1, 100),
            array_map('intval', $rows->pluck('range')->values()->toArray())
        );
    }

    /**
     * 测试 queryByUniqueConditions 方法：空条件返回空集合.
     */
    public function testQueryByUniqueConditionsEmpty()
    {
        $result = User::query()->queryByUniqueConditions(Collection::make());

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /**
     * 测试 queryByUniqueConditions 方法：单列条件（非联合唯一键）.
     */
    public function testQueryByUniqueConditionsSingleColumn()
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->nickname = sprintf('user-%s', $i);
            $user->save();
        }

        $conditions = Collection::make([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(3, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：单列单值条件.
     */
    public function testQueryByUniqueConditionsSingleValue()
    {
        $user = new User();
        $user->nickname = 'single-user';
        $user->save();

        $conditions = Collection::make([
            ['id' => $user->id],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(1, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：null 值条件.
     */
    public function testQueryByUniqueConditionsWithNull()
    {
        $user1 = new User();
        $user1->nickname = null;
        $user1->save();

        $user2 = new User();
        $user2->nickname = 'not-null';
        $user2->save();

        // 测试查询 null 值
        $conditions = Collection::make([
            ['nickname' => null],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(1, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：联合唯一键（只有一个 In 操作）.
     */
    public function testQueryByUniqueConditionsCompositeOneIn()
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->nickname = sprintf('composite-user-%s', $i);
            $user->range = 100;  // 相同的 range
            $user->save();
        }

        // 联合唯一键：nickname 多个值，range 单个值
        $conditions = Collection::make([
            ['nickname' => 'composite-user-1', 'range' => 100],
            ['nickname' => 'composite-user-2', 'range' => 100],
            ['nickname' => 'composite-user-3', 'range' => 100],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(3, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：联合唯一键（多个 In 操作 - 兜底方案）.
     */
    public function testQueryByUniqueConditionsCompositeMultipleIn()
    {
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->nickname = sprintf('multi-user-%s', $i);
            $user->range = $i * 10;
            $user->save();
        }

        // 联合唯一键：nickname 和 range 都有多个不同的值
        $conditions = Collection::make([
            ['nickname' => 'multi-user-1', 'range' => 10],
            ['nickname' => 'multi-user-2', 'range' => 20],
            ['nickname' => 'multi-user-3', 'range' => 30],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(3, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：不存在的记录.
     */
    public function testQueryByUniqueConditionsNotFound()
    {
        $user = new User();
        $user->nickname = 'exists';
        $user->save();

        $conditions = Collection::make([
            ['id' => 999],
            ['id' => 1000],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertTrue($result->isEmpty());
    }

    /**
     * 测试 queryByUniqueConditions 方法：部分存在.
     */
    public function testQueryByUniqueConditionsPartialFound()
    {
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->nickname = sprintf('partial-user-%s', $i);
            $user->save();
        }

        $conditions = Collection::make([
            ['id' => 1],
            ['id' => 2],
            ['id' => 999],  // 不存在
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(2, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：结果以缓存键为 key.
     */
    public function testQueryByUniqueConditionsKeyedByCacheKey()
    {
        $user = new User();
        $user->nickname = 'keyed-user';
        $user->save();

        $conditions = Collection::make([
            ['id' => $user->id],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        // 验证返回的 Collection 是以缓存键为 key
        $expectedCacheKey = $user->makeColumnsCacheKey(['id' => $user->id]);
        $this->assertTrue($result->has($expectedCacheKey));
        $this->assertSame($user->id, $result->get($expectedCacheKey)->id);
    }

    /**
     * 测试 queryByUniqueConditions 方法：联合唯一键中单值字段为 null（分支2 null处理）.
     */
    public function testQueryByUniqueConditionsCompositeOneInWithNullSingleValue()
    {
        // 创建 range 为 null 的用户
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->nickname = sprintf('null-range-user-%s', $i);
            $user->range = null;
            $user->save();
        }

        // 联合唯一键：nickname 多个值，range 单个 null 值
        $conditions = Collection::make([
            ['nickname' => 'null-range-user-1', 'range' => null],
            ['nickname' => 'null-range-user-2', 'range' => null],
            ['nickname' => 'null-range-user-3', 'range' => null],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(3, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：兜底方案中包含 null 值（分支3 null处理）.
     */
    public function testQueryByUniqueConditionsCompositeMultipleInWithNull()
    {
        // 创建数据
        $user1 = new User();
        $user1->nickname = 'fallback-user-1';
        $user1->range = 10;
        $user1->save();

        $user2 = new User();
        $user2->nickname = 'fallback-user-2';
        $user2->range = null;  // null 值
        $user2->save();

        $user3 = new User();
        $user3->nickname = 'fallback-user-3';
        $user3->range = 30;
        $user3->save();

        // 联合唯一键：nickname 和 range 都有多个不同的值，包含 null
        $conditions = Collection::make([
            ['nickname' => 'fallback-user-1', 'range' => 10],
            ['nickname' => 'fallback-user-2', 'range' => null],
            ['nickname' => 'fallback-user-3', 'range' => 30],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(3, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：单列多个 null 值条件.
     */
    public function testQueryByUniqueConditionsMultipleNullValues()
    {
        $user1 = new User();
        $user1->nickname = null;
        $user1->save();

        $user2 = new User();
        $user2->nickname = 'has-name';
        $user2->save();

        // 多个相同的 null 值条件
        $conditions = Collection::make([
            ['nickname' => null],
            ['nickname' => null],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        // 应该只返回 1 条（因为都是查询 nickname = null）
        $this->assertSame(1, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：非联合唯一键多值情况中的去重.
     */
    public function testQueryByUniqueConditionsSingleColumnDuplicateValues()
    {
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->nickname = sprintf('dup-user-%s', $i);
            $user->save();
        }

        // 有重复的 id
        $conditions = Collection::make([
            ['id' => 1],
            ['id' => 1],
            ['id' => 2],
            ['id' => 2],
            ['id' => 3],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        // 应该返回 3 条（去重后）
        $this->assertSame(3, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：混合 null 和非 null 值.
     */
    public function testQueryByUniqueConditionsMixedNullAndNonNull()
    {
        $user1 = new User();
        $user1->nickname = null;
        $user1->save();

        $user2 = new User();
        $user2->nickname = 'named-user';
        $user2->save();

        $user3 = new User();
        $user3->nickname = 'another-user';
        $user3->save();

        // 混合 null 和非 null 条件
        $conditions = Collection::make([
            ['nickname' => null],
            ['nickname' => 'named-user'],
            ['nickname' => 'another-user'],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(3, $result->count());
    }

    /**
     * 测试 queryByUniqueConditions 方法：联合唯一键中混合 null 和非 null（分支2）.
     */
    public function testQueryByUniqueConditionsCompositeOneInMixedNull()
    {
        // 创建测试数据
        $user1 = new User();
        $user1->nickname = 'mixed-user-1';
        $user1->range = null;  // null
        $user1->save();

        $user2 = new User();
        $user2->nickname = 'mixed-user-2';
        $user2->range = 100;   // 非 null
        $user2->save();

        $user3 = new User();
        $user3->nickname = 'mixed-user-3';
        $user3->range = null;  // null
        $user3->save();

        // 联合唯一键：nickname 多个值，range 混合 null 和非 null（但只有一个 In 操作）
        // 这里 range 有 [null, 100, null] = [null, 100]，只有 2 个不同值
        // nickname 有 3 个不同值
        // 所以只有 nickname 有多于 1 个值的 In 操作
        $conditions = Collection::make([
            ['nickname' => 'mixed-user-1', 'range' => null],
            ['nickname' => 'mixed-user-2', 'range' => 100],
            ['nickname' => 'mixed-user-3', 'range' => null],
        ]);

        $result = User::query()->queryByUniqueConditions($conditions);

        $this->assertSame(3, $result->count());
    }
}
