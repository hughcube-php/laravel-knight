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

    public function testWhereLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s%%', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->whereLike('nickname', sprintf('%%%s%%', $keyword))->first();
        $this->assertInstanceOf(User::class, $user);

        /** @var User $user */
        $user = User::query()->whereLike('nickname', $keyword)->first();
        $this->assertNull($user);

        /** @var User $user */
        $user = User::query()->whereEscapeLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);

        /** @var User $user */
        $user = User::query()->whereRaw("nickname LIKE '%%%'")->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereLeftLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->whereEscapeLeftLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereRightLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', Str::random(), $keyword);
        $user->save();

        /** @var User $user */
        $user = User::query()->whereEscapeRightLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->orWhereEscapeLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereLeftLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->orWhereEscapeLeftLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereRightLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', Str::random(), $keyword);
        $user->save();

        /** @var User $user */
        $user = User::query()->orWhereEscapeRightLike('nickname', $keyword)->first();
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
