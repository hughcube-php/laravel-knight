<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/5
 * Time: 2:49 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use Exception;
use HughCube\Laravel\Knight\Database\Eloquent\Collection as KnightCollection;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ModelTest extends TestCase
{
    protected $cacheForgetCount = 0;

    protected $cacheMissedCount = 0;

    protected $cacheHitCount = 0;

    protected $cachePutCount = 0;

    protected $databaseQueryCount = 0;

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

        DB::listen(function () {
            $this->databaseQueryCount++;
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

    protected function assertDatabaseQueryCount($value, $callable)
    {
        $count = $this->databaseQueryCount;
        $results = $callable();
        $this->assertSame($count + $value, $this->databaseQueryCount);

        return $results;
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

    public function testIsMatchPk()
    {
        $this->resetTable();

        $this->assertTrue(!User::isMatchPk(0));
        $this->assertTrue(!User::isMatchPk(null));
        $this->assertTrue(!User::isMatchPk(''));

        $this->assertTrue(User::isMatchPk(1));
        $this->assertTrue(User::isMatchPk('1'));

        $this->assertDatabaseQueryCount(0, function () {
            $this->assertCacheHitCount(0, function () {
                $this->assertCacheForgetCount(0, function () {
                    $this->assertCacheMissedAndPutCount(0, function () {
                        User::findByIds([0]);
                        User::findByIds([null, 0, '']);
                    });
                });
            });
        });

        $this->assertDatabaseQueryCount(1, function () {
            $this->assertCacheMissedAndPutCount(1, function () {
                User::findByIds([null, 0, 1]);
            });
        });
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
        $this->assertCacheMissedAndPutCount(2, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
            $this->assertFalse($user->isFromCache());

            $user = User::findById(1);
            $this->assertTrue($user->isFromCache());

            sleep(11);
            $user = User::findById(1);
            $this->assertFalse($user->isFromCache());
        });

        /** hit */
        $this->assertCacheHitCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
            $this->assertTrue($user->isFromCache());
        });

        /** hit */
        $this->assertCacheHitCount(3, function () {
            $user = User::findById(1);
            $this->assertTrue($user->isFromCache());

            $user->deleteRowCache();
            $user = User::findById(1);
            $this->assertFalse($user->isFromCache());

            $user = User::query()->findUniqueRow(['nickname' => $user->nickname]);
            $this->assertFalse($user->isFromCache());

            $user->resetRowCache();

            $user = User::findById(1);
            $this->assertTrue($user->isFromCache());

            $user = User::query()->findUniqueRow(['nickname' => $user->nickname]);
            $this->assertTrue($user->isFromCache());

            sleep(11);
            $user = User::findById(1);
            $this->assertFalse($user->isFromCache());
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
    public function testFindById()
    {
        $this->resetTable();

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertNull($user);
        });

        $this->assertCacheHitCount(1, function () {
            $user = User::findById(1);
            $this->assertNull($user);
        });

        $this->createUsers();

        $this->assertCacheMissedAndPutCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
        });

        $this->assertCacheHitCount(1, function () {
            $user = User::findById(1);
            $this->assertInstanceOf(User::class, $user);
        });
    }

    /**
     * @throws Exception
     *
     * @return void
     */
    public function testFindByIds()
    {
        $this->resetTable();
        $cacheIds = Collection::make();

        $this->assertDatabaseQueryCount(1, function () {
            $this->assertCacheMissedAndPutCount(100, function () {
                $users = User::findByIds(range(1, 100));
                $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
                $this->assertTrue($users->isEmpty());
            });
        });

        $this->assertDatabaseQueryCount(0, function () {
            $this->assertCacheHitCount(100, function () {
                $users = User::findByIds(range(1, 100));
                $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
                $this->assertTrue($users->isEmpty());
            });
        });

        $this->createUsers(100);

        $this->assertCacheMissedAndPutCount(20, function () use (&$cacheIds) {
            $ids = Collection::make([
                1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
                1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010,
            ])->shuffle()->shuffle()->values();

            $users = User::findByIds($ids);
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);

            /** 查询结果正确 */
            $this->assertSame(10, $users->count());

            /** isFromCache正确 */
            $users->each(function (User $user) {
                $this->assertFalse($user->isFromCache());
            });

            /** id排序正确 */
            $this->assertSame(
                $ids->filter(function ($item) {
                    return 1000 > $item;
                })->values()->toArray(),
                $users->keys()->values()->toArray()
            );

            $cacheIds = $cacheIds->merge($ids);
        });

        $this->assertCacheHitCount(10, function () use (&$cacheIds) {
            $this->assertCacheMissedAndPutCount(10, function () use (&$cacheIds) {
                $ids = Collection::make([
                    6, 7, 8, 9, 10, 1001, 1002, 1003, 1004, 1005,
                    11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
                ])->shuffle()->shuffle()->values();

                $users = User::findByIds($ids);
                $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);

                /** 查询结果正确 */
                $this->assertSame(15, $users->count());

                /** isFromCache正确 */
                $users->each(function (User $user) use ($cacheIds) {
                    $this->assertSame($cacheIds->contains($user->id), $user->isFromCache());
                });

                /** id排序正确 */
                $this->assertSame(
                    $ids->filter(function ($item) {
                        return 1000 > $item;
                    })->values()->toArray(),
                    $users->keys()->values()->toArray()
                );

                $cacheIds = $cacheIds->merge($ids);
            });
        });

        $this->assertDatabaseQueryCount(0, function () use ($cacheIds) {
            User::findByIds($cacheIds);
        });
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
        $user->timestamps = false;
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
     * @return void
     * @throws Exception
     *
     */
    public function testUniqueRows()
    {
        $this->resetTable();

        $condition = Collection::make();
        for ($i = 1; $i <= 100; $i++) {
            $user = new User();
            $user->nickname = sprintf('nickname-%s', $i);
            $user->save();

            $condition->add(['id' => $user->id, 'nickname' => $user->nickname]);
        }

        /** @var Collection<integer, User> $rows */
        $rows = User::query()->findUniqueRows($condition);
        $this->assertSame($rows->count(), $condition->count());
        foreach ($rows as $row) {
            $this->assertFalse($row->isFromCache());
        }

        /** @var Collection<integer, User> $rows */
        $rows = User::query()->findUniqueRows($condition);
        $this->assertSame($rows->count(), $condition->count());
        foreach ($rows as $row) {
            $this->assertTrue($row->isFromCache());
        }
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
        $queryUser = User::query()->whereLike('nickname', $keyword)->first();
        $this->assertSame($user->id, $queryUser->id);

        $keyword = md5(random_bytes(1000)).$user->nickname;
        $queryUser = User::query()->whereLike('nickname', $keyword)->first();
        $this->assertNull($queryUser);

        $keyword = substr($user->nickname, 0, 10);
        $this->assertTrue(Str::startsWith($user->nickname, $keyword));
        $queryUser = User::query()->whereLeftLike('nickname', $keyword)->first();
        $this->assertSame($user->id, $queryUser->id);

        $keyword = substr($user->nickname, -10);
        $this->assertTrue(Str::endsWith($user->nickname, $keyword));
        $queryUser = User::query()->whereRightLike('nickname', $keyword)->first();
        $this->assertSame($user->id, $queryUser->id);
    }

    public function testQueryCollection()
    {
        $this->resetTable();

        $users = User::query()->limit(10)->get();

        $this->assertInstanceOf(KnightCollection::class, $users);
        $this->assertInstanceOf(KnightCollection::class, User::findByIds($users->pluck('id')));
    }
}
