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

class ModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->comment('id');
            $table->string('nickname')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function testQuery()
    {
        User::query()->truncate();

        $this->assertSame(0, User::query()->count());
        for ($i = 1; $i <= 1000; $i++) {
            $user = new User();
            $user->nickname = md5(random_bytes(1000));
            $user->save();
        }
        $this->assertSame(1000, User::query()->count());

        $cacheIds = Collection::make();

        for ($i = 1; $i <= 100; $i++) {
            $user = User::findById($i);
            $this->assertFalse($user->isFromCache());

            $user = User::findById($i);
            $this->assertTrue($user->isFromCache());

            $cacheIds->add($i);
        }

        for ($i = 1; $i <= 1000; $i++) {
            $startId = random_int(0, 990);
            $endId = $startId + 10;

            $users = User::findByIds(range($startId, $endId));
            foreach ($users as $user) {
                $this->assertSame($user->isFromCache(), (false !== $cacheIds->search($user->id)));
                $cacheIds->add($user->id);
            }
        }

        $userIds = Collection::make(range(1, 100))->shuffle();
        $users = User::findByIds($userIds->toArray());
        $this->assertSame($users->keys()->toArray(), $userIds->toArray());
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        parent::tearDown();
    }
}
