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
        $user = User::query()->whereLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);

        /** @var User $user */
        $user = User::query()->whereLike('nickname', '%')->first();
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
        $user = User::query()->whereLeftLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testWhereRightLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', Str::random(), $keyword);
        $user->save();

        /** @var User $user */
        $user = User::query()->whereRightLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s%s', Str::random(), $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->orWhereLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereLeftLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', $keyword, Str::random());
        $user->save();

        /** @var User $user */
        $user = User::query()->orWhereLeftLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }

    public function testOrWhereRightLike()
    {
        $keyword = __FUNCTION__;

        $user = new User();
        $user->nickname = sprintf('%s%s', Str::random(), $keyword);
        $user->save();

        /** @var User $user */
        $user = User::query()->orWhereRightLike('nickname', $keyword)->first();
        $this->assertInstanceOf(User::class, $user);
    }
}
