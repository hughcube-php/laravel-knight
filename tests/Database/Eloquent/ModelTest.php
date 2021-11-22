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
use Psr\SimpleCache\InvalidArgumentException;

class ModelTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->comment('id');
            $table->string('nickname')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testQuery()
    {
        User::query()->truncate();

        $this->assertNull(User::findById(1));

        $this->assertSame(0, User::query()->count());
        for ($i = 1; $i <= 1000; $i++) {
            $user = new User();
            $user->nickname = md5(random_bytes(1000));
            $user->save();
        }
        $this->assertSame(1000, User::query()->count());

        $this->assertNull(User::findById(1));
        /** @var User $user */
        $user = User::query()->noCache()->findByPk(1);
        $user->refreshRowCache();

        $cacheIds = Collection::make();

        /** @var User $user */
        for ($i = 1; $i <= 100; $i++) {
            $user = User::findById($i);
            $this->assertFalse($user->isFromCache());

            $user = User::findById($i);
            $this->assertTrue($user->isFromCache());
            $cacheIds->push($i);
        }

        for ($i = 1; $i <= 1000; $i++) {
            $startId = random_int(1, 900);
            $endId = $startId + 10;

            $ids = range($startId, $endId);
            $users = User::findByIds($ids);
            $this->assertSame(count($ids), $users->count());
            $this->assertInstanceOf(Collection::class, $users);
            foreach ($users as $user) {
                $this->assertSame($user->isFromCache(), (false !== $cacheIds->search($user->id)));
                $cacheIds->push($user->id);
            }
        }

        $userIds = Collection::make(range(1, 100))->shuffle();
        $users = User::findByIds($userIds->toArray());
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertSame($users->keys()->toArray(), $userIds->toArray());

        $users = User::noCacheQuery()->findByPks(range(1, 100));
        foreach ($users as $user) {
            $this->assertFalse($user->isFromCache());
        }

        $manyColumnItems = [];
        $users = User::noCacheQuery()->findByPks(range(1, 100));
        foreach ($users as $user) {
            $where = ['id' => $user->id, 'nickname' => $user->nickname];
            $user = User::query()->findUniqueRows([$where])->first();
            $this->assertFalse($user->isFromCache());

            $user = User::query()->findUniqueRows([$where])->first();
            $this->assertTrue($user->isFromCache());

            $manyColumnItems[] = $where;
        }

        $users = User::query()->findUniqueRows($manyColumnItems);
        $this->assertSame(count($manyColumnItems), $users->count());
        foreach ($users as $user) {
            $this->assertTrue($user->isFromCache());
        }
    }
}
