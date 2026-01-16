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
            $rows->pluck('range')->values()->toArray()
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->whereRange('range', [11, 20])
            ->get();
        $this->assertSame(
            range(11, 20),
            $rows->pluck('range')->values()->toArray()
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->whereRange('range', [null, 20])
            ->get();
        $this->assertSame(
            range(1, 20),
            $rows->pluck('range')->values()->toArray()
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->whereRange('range', [20, null])
            ->get();
        $this->assertSame(
            range(20, 100),
            $rows->pluck('range')->values()->toArray()
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
            $rows->pluck('range')->values()->toArray()
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->orWhereRange('range', [11, 20])
            ->get();
        $this->assertSame(
            range(1, 100),
            $rows->pluck('range')->values()->toArray()
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
            $rows->pluck('range')->values()->toArray()
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->whereNotRange('range', [91, 100])
            ->get();
        $this->assertSame(
            range(1, 90),
            $rows->pluck('range')->values()->toArray()
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
            $rows->pluck('range')->values()->toArray()
        );

        $rows = User::query()
            ->whereRaw(sprintf("nickname LIKE '%s%%'", $keyword))
            ->orWhereNotRange('range', [91, 100])
            ->get();
        $this->assertSame(
            range(1, 100),
            $rows->pluck('range')->values()->toArray()
        );
    }
}
