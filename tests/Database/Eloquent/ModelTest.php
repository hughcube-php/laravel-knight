<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/5
 * Time: 2:49 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use Exception;
use HughCube\Laravel\Knight\Support\Carbon;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ModelTest extends TestCase
{
    protected $cacheForgetCount = 0;

    protected $cacheMissedCount = 0;

    protected $cacheHitCount = 0;

    protected $cachePutCount = 0;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        Schema::dropIfExists('users');
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned()->comment('id');
            $table->string('nickname')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->listenCacheEvent(KeyForgotten::class, function () {
            $this->cacheForgetCount++;
        });

        $this->listenCacheEvent(CacheMissed::class, function () {
            $this->cacheMissedCount++;
        });

        $this->listenCacheEvent(CacheHit::class, function () {
            $this->cacheHitCount++;
        });

        $this->listenCacheEvent(KeyWritten::class, function () {
            $this->cachePutCount++;
        });
    }

    protected function assertCacheForgetCount($value, $callable)
    {
        $count = $this->cacheForgetCount;
        $results = $callable();
        $this->assertSame($count + $value, $this->cacheForgetCount);

        return $results;
    }

    protected function assertCacheMissedCount($value, $callable)
    {
        $count = $this->cacheMissedCount;
        $results = $callable();
        $this->assertSame($count + $value, $this->cacheMissedCount);

        return $results;
    }

    protected function assertCacheHitCount($value, $callable)
    {
        $count = $this->cacheHitCount;
        $results = $callable();
        $this->assertSame($count + $value, $this->cacheHitCount);

        return $results;
    }

    protected function assertCachePutCount($value, $callable)
    {
        $count = $this->cachePutCount;
        $results = $callable();
        $this->assertSame($count + $value, $this->cachePutCount);

        return $results;
    }

    protected function assertCacheMissedAndPutCount($value, $callable)
    {
        return $this->assertCachePutCount($value, function () use ($value, $callable) {
            return $this->assertCacheMissedCount($value, $callable);
        });
    }

    protected function listenCacheEvent($events, $listener = null)
    {
        $dispatcher = app(Dispatcher::class);

        if ($dispatcher instanceof Dispatcher) {
            $dispatcher->listen($events, $listener);
        }
    }

    protected function resetTable()
    {
        User::query()->truncate();
        User::query()->getCache()->clear();
    }

    /**
     * @param int $count
     *
     * @throws Exception
     *
     * @return void
     */
    protected function createUsers(int $count = 1)
    {
        for ($i = 1; $i <= $count; $i++) {
            $user = new User();
            $user->nickname = md5(random_bytes(1000));
            $user->save();
        }
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testCacheOnCreate()
    {
        $this->resetTable();

        /** Miss */
        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertNull($user);
        });

        /** hit */
        $this->assertCacheHitCount(1, function () {
            $user = User::findById(1);
            $this->assertNull($user);
        });

        $this->createUsers();

        /** miss */
        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
            $this->assertFalse($user->isFromCache());
        });

        /** hit */
        $this->assertCacheHitCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
            $this->assertTrue($user->isFromCache());
        });
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testCacheOnUpdate()
    {
        $this->resetTable();
        $this->createUsers();

        $nickname = Str::random();

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
        });

        $this->assertCacheForgetCount(1, function () use ($nickname) {
            $user = User::findById(1);
            $user->nickname = $nickname;
            $user->save();
        });

        $this->assertCacheMissedAndPutCount(1, function () use ($nickname) {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
            $this->assertFalse($user->isFromCache());
            $this->assertSame($user->nickname, $nickname);
        });

        $this->assertCacheHitCount(1, function () use ($nickname) {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
            $this->assertTrue($user->isFromCache());
            $this->assertSame($user->nickname, $nickname);
        });
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testCacheOnForceDelete()
    {
        $this->resetTable();
        $this->createUsers();

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
        });

        $this->assertCacheForgetCount(1, function () {
            $user = User::findById(1);
            $user->forceDelete();
        });

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertNull($user);
        });

        $this->assertSame(0, User::query()->count());
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testCacheOnDelete()
    {
        $this->resetTable();
        $this->createUsers();

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
        });

        $this->assertCacheForgetCount(1, function () {
            $user = User::findById(1);
            $user->delete();
        });

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertNull($user);
        });

        $this->assertCacheForgetCount(1, function () {
            $user = User::withTrashed()->whereKey(1)->first();
            $this->assertInstanceOf(User::class, $user);
            $user->nickname = Str::random();
            $user->save();
        });

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertNull($user);
        });

        $this->assertCacheForgetCount(1, function () {
            $user = User::withTrashed()->whereKey(1)->first();
            $this->assertInstanceOf(User::class, $user);
            $user->restore();
        });

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
            $this->assertFalse($user->isFromCache());
        });

        $this->assertCacheHitCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
            $this->assertTrue($user->isFromCache());
        });

        $this->assertSame(1, User::withTrashed()->count());
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testQuery()
    {
        $this->markTestSkipped();

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

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testConversionDateTime()
    {
        $this->resetTable();

        $user = new User();
        $user->nickname = md5(random_bytes(1000));
        $user->save();

        /** @var User $user */
        $user = User::withTrashed()->first();
        $this->assertInstanceOf(Carbon::class, $user->created_at);
        $this->assertInstanceOf(Carbon::class, $user->updated_at);
        $this->assertNull($user->deleted_at);

        $now = Carbon::now();
        $user->created_at = $now;
        $user->updated_at = $now;
        $user->deleted_at = $now;
        $user->save();

        /** @var User $user */
        $user = User::withTrashed()->first();
        $this->assertFalse($user->isAvailable());
        $this->assertTrue($user->isDeleted());

        /** @var User $user */
        $user = User::withTrashed()->first();
        $this->assertSame($user->created_at->getTimestamp(), $now->getTimestamp());
        $this->assertSame($user->updated_at->getTimestamp(), $now->getTimestamp());
        $this->assertSame($user->deleted_at->getTimestamp(), $now->getTimestamp());

        /** @var User $user */
        $user = User::withTrashed()->first();
        $format = 'Y-m-d H:i:s';
        $this->assertSame($user->formatCreatedAt($format), $now->format($format));
        $this->assertSame($user->formatUpdatedAt($format), $now->format($format));
        $this->assertSame($user->formatDeleteAt($format), $now->format($format));

        $user->created_at = null;
        $user->updated_at = null;
        $user->deleted_at = null;
        $user->save();
        /** @var User $user */
        $user = User::withTrashed()->first();
        $this->assertTrue($user->isAvailable());
        $this->assertFalse($user->isDeleted());

        $this->assertNull($user->created_at);
        $this->assertNull($user->updated_at);
        $this->assertNull($user->deleted_at);

        $this->assertNull($user->formatCreatedAt());
        $this->assertNull($user->formatCreatedAt());
        $this->assertNull($user->formatCreatedAt());
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testQueryWhereLike()
    {
        $this->resetTable();

        $user = new User();
        $user->nickname = md5(random_bytes(1000));
        $user->save();

        $keyword = substr(substr($user->nickname, 10), -10);
        $queryUser = User::withTrashed()->whereLike('nickname', $keyword)->first();
        $this->assertSame($user->id, $queryUser->id);

        $keyword = md5(random_bytes(1000)).$user->nickname;
        $queryUser = User::withTrashed()->whereLike('nickname', $keyword)->first();
        $this->assertNull($queryUser);

        $keyword = substr($user->nickname, 0, 10);
        $this->assertTrue(Str::startsWith($user->nickname, $keyword));
        $queryUser = User::withTrashed()->whereLeftLike('nickname', $keyword)->first();
        $this->assertSame($user->id, $queryUser->id);

        $keyword = substr($user->nickname, -10);
        $this->assertTrue(Str::endsWith($user->nickname, $keyword));
        $queryUser = User::withTrashed()->whereRightLike('nickname', $keyword)->first();
        $this->assertSame($user->id, $queryUser->id);
    }
}
