<?php

namespace HughCube\Laravel\Knight\Tests\Console\Traits;

use Exception;
use HughCube\Laravel\Knight\Console\Command;
use HughCube\Laravel\Knight\Console\Traits\HasRefreshModelCache;
use HughCube\Laravel\Knight\Database\Eloquent\Model;
use HughCube\Laravel\Knight\Database\Eloquent\Traits\SoftDeletes;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Psr\SimpleCache\CacheInterface;

class HasRefreshModelCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('refresh_cache_test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->default('');
            $table->string('email')->default('');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('refresh_cache_test_models');

        parent::tearDown();
    }

    protected function makeCommand(): RefreshModelCacheTestCommand
    {
        $command = new RefreshModelCacheTestCommand();
        $command->setLaravel($this->app);

        return $command;
    }

    /**
     * 空查询结果应提前返回并输出提示信息
     */
    public function testEmptyQueryEarlyReturn()
    {
        $command = $this->makeCommand();

        $query = RefreshCacheTestModel::query();

        $command->callEachRefreshModelCache($query);

        $this->assertCount(1, $command->infoMessages);
        $this->assertStringContainsString('未查询到数据', $command->infoMessages[0]);
        $this->assertStringContainsString('RefreshCacheTestModel', $command->infoMessages[0]);
    }

    /**
     * 空查询结果不应进入 eachById 循环
     */
    public function testEmptyQueryNoIteration()
    {
        $command = $this->makeCommand();

        $query = RefreshCacheTestModel::query();
        $command->callEachRefreshModelCache($query);

        // 只有一条"未查询到数据"的消息, 没有任何刷新/跳过消息
        $this->assertCount(1, $command->infoMessages);
        foreach ($command->infoMessages as $msg) {
            $this->assertStringNotContainsString('成功', $msg);
            $this->assertStringNotContainsString('跳过', $msg);
        }
    }

    /**
     * force=true 时应强制刷新所有记录的缓存
     */
    public function testForceRefreshAll()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        RefreshCacheTestModel::create(['name' => 'Bob', 'email' => 'bob@test.com']);
        RefreshCacheTestModel::create(['name' => 'Charlie', 'email' => 'charlie@test.com']);

        $command = $this->makeCommand();
        $query = RefreshCacheTestModel::noCacheQuery();

        $command->callEachRefreshModelCache($query, true);

        $this->assertCount(3, $command->infoMessages);
        foreach ($command->infoMessages as $message) {
            $this->assertStringContainsString('成功', $message);
        }

        $this->assertStringContainsString('1/3', $command->infoMessages[0]);
        $this->assertStringContainsString('2/3', $command->infoMessages[1]);
        $this->assertStringContainsString('3/3', $command->infoMessages[2]);
    }

    /**
     * force=true 时即使缓存已一致也应强制刷新
     */
    public function testForceRefreshEvenWhenCacheConsistent()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        // 先 force 刷新建立一致缓存
        $command1 = $this->makeCommand();
        $command1->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        // 再次 force 刷新, 仍然应显示成功
        $command2 = $this->makeCommand();
        $command2->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        $this->assertCount(1, $command2->infoMessages);
        $this->assertStringContainsString('成功', $command2->infoMessages[0]);
    }

    /**
     * force=false 且缓存一致时应跳过刷新
     * 先通过 force refresh 建立与 DB 一致的缓存, 然后 non-force 应跳过
     */
    public function testSkipWhenCacheConsistent()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        // 先 force 刷新, 让缓存与 DB 完全一致
        $command1 = $this->makeCommand();
        $command1->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        // 再 non-force 刷新, 应跳过
        $command2 = $this->makeCommand();
        $command2->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), false);

        $this->assertCount(1, $command2->infoMessages);
        $this->assertStringContainsString('跳过', $command2->infoMessages[0]);
    }

    /**
     * force=false 且缓存不一致时应刷新
     */
    public function testRefreshWhenCacheInconsistent()
    {
        $model = RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        // force 刷新建立缓存
        $command1 = $this->makeCommand();
        $command1->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        // 直接修改数据库但不清缓存, 制造不一致
        RefreshCacheTestModel::noCacheQuery()->where('id', $model->id)->update(['name' => 'AliceUpdated']);

        $command2 = $this->makeCommand();
        $command2->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), false);

        $this->assertCount(1, $command2->infoMessages);
        $this->assertStringContainsString('成功', $command2->infoMessages[0]);

        // 验证缓存已被刷新为最新值
        $cached = RefreshCacheTestModel::findById($model->id);
        $this->assertSame('AliceUpdated', $cached->name);
    }

    /**
     * force=false 且缓存不存在时, findById 穿透到 DB, 与 eachById 结果一致应跳过
     */
    public function testSkipWhenCacheNotExistsButDbConsistent()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        // 确保缓存中没有数据 (findById 会穿透到 DB, 返回的 model 与 eachById 一致)
        $command = $this->makeCommand();
        $query = RefreshCacheTestModel::noCacheQuery();

        $command->callEachRefreshModelCache($query, false);

        $this->assertCount(1, $command->infoMessages);
        $this->assertStringContainsString('跳过', $command->infoMessages[0]);
    }

    /**
     * 刷新成功后缓存应与数据库一致
     */
    public function testCacheConsistencyAfterRefresh()
    {
        $model = RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        $command = $this->makeCommand();
        $query = RefreshCacheTestModel::noCacheQuery();

        $command->callEachRefreshModelCache($query, true);

        $cached = RefreshCacheTestModel::findById($model->id);
        $this->assertNotNull($cached);

        $fresh = RefreshCacheTestModel::noCacheQuery()->find($model->id);
        $this->assertTrue($fresh->equal($cached));
    }

    /**
     * 刷新成功后, 再次 non-force 应全部跳过
     */
    public function testAllSkipAfterForceRefresh()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        RefreshCacheTestModel::create(['name' => 'Bob', 'email' => 'bob@test.com']);

        // 先 force 刷新
        $command1 = $this->makeCommand();
        $command1->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        // 再 non-force 刷新, 应全部跳过
        $command2 = $this->makeCommand();
        $command2->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), false);

        $this->assertCount(2, $command2->infoMessages);
        foreach ($command2->infoMessages as $msg) {
            $this->assertStringContainsString('跳过', $msg);
        }
    }

    /**
     * 多条记录应按序全部处理, 计数器正确递增
     */
    public function testMultipleRecordsProcessedInOrder()
    {
        for ($i = 1; $i <= 5; $i++) {
            RefreshCacheTestModel::create(['name' => "User{$i}", 'email' => "user{$i}@test.com"]);
        }

        $command = $this->makeCommand();
        $query = RefreshCacheTestModel::noCacheQuery();

        $command->callEachRefreshModelCache($query, true);

        $this->assertCount(5, $command->infoMessages);

        for ($i = 0; $i < 5; $i++) {
            $expected = ($i + 1) . '/5';
            $this->assertStringContainsString($expected, $command->infoMessages[$i]);
        }
    }

    /**
     * 使用带条件的 query 应只处理匹配的记录
     */
    public function testFilteredQuery()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        RefreshCacheTestModel::create(['name' => 'Bob', 'email' => 'bob@test.com']);
        RefreshCacheTestModel::create(['name' => 'Charlie', 'email' => 'charlie@test.com']);

        $command = $this->makeCommand();
        $query = RefreshCacheTestModel::noCacheQuery()->where('name', 'Bob');

        $command->callEachRefreshModelCache($query, true);

        $this->assertCount(1, $command->infoMessages);
        $this->assertStringContainsString('1/1', $command->infoMessages[0]);
    }

    /**
     * 带条件的 query 不应影响其他记录的缓存
     */
    public function testFilteredQueryDoesNotAffectOtherRecords()
    {
        $alice = RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $bob = RefreshCacheTestModel::create(['name' => 'Bob', 'email' => 'bob@test.com']);

        // 为 Alice 建立缓存
        $command1 = $this->makeCommand();
        $command1->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        // 修改 Alice 和 Bob 的 DB 数据
        RefreshCacheTestModel::noCacheQuery()->where('id', $alice->id)->update(['name' => 'AliceUpdated']);
        RefreshCacheTestModel::noCacheQuery()->where('id', $bob->id)->update(['name' => 'BobUpdated']);

        // 只刷新 Bob
        $command2 = $this->makeCommand();
        $command2->callEachRefreshModelCache(
            RefreshCacheTestModel::noCacheQuery()->where('name', 'BobUpdated'),
            true
        );

        // Alice 的缓存应该还是旧的
        $cachedAlice = RefreshCacheTestModel::findById($alice->id);
        $this->assertSame('Alice', $cachedAlice->name);

        // Bob 的缓存应该是新的
        $cachedBob = RefreshCacheTestModel::findById($bob->id);
        $this->assertSame('BobUpdated', $cachedBob->name);
    }

    /**
     * info 输出应包含正确的模型类名和主键
     */
    public function testInfoMessageContainsModelClassAndKey()
    {
        $model = RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        $command = $this->makeCommand();
        $query = RefreshCacheTestModel::noCacheQuery();

        $command->callEachRefreshModelCache($query, true);

        $this->assertCount(1, $command->infoMessages);
        $this->assertStringContainsString('RefreshCacheTestModel', $command->infoMessages[0]);
        $this->assertStringContainsString((string) $model->id, $command->infoMessages[0]);
    }

    /**
     * 默认 force 参数应为 true
     */
    public function testDefaultForceIsTrue()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        // 先 force 建立一致缓存
        $command1 = $this->makeCommand();
        $command1->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        // 不传 force 参数, 默认 true, 即使缓存一致也应该刷新
        $command2 = $this->makeCommand();
        $command2->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery());

        $this->assertCount(1, $command2->infoMessages);
        $this->assertStringContainsString('成功', $command2->infoMessages[0]);
    }

    /**
     * 多条记录中混合跳过和成功的场景:
     * 先建立一致缓存, 然后只修改部分记录的 DB, non-force 刷新
     */
    public function testMixedSkipAndRefresh()
    {
        $alice = RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        $bob = RefreshCacheTestModel::create(['name' => 'Bob', 'email' => 'bob@test.com']);

        // 先 force 刷新, 建立一致缓存
        $command1 = $this->makeCommand();
        $command1->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        // 只修改 Bob 的 DB 数据, 不清缓存
        RefreshCacheTestModel::noCacheQuery()->where('id', $bob->id)->update(['name' => 'BobUpdated']);

        // non-force 刷新: Alice 应跳过, Bob 应成功
        $command2 = $this->makeCommand();
        $command2->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), false);

        $this->assertCount(2, $command2->infoMessages);

        $results = array_map(function ($msg) {
            if (strpos($msg, '跳过') !== false) {
                return '跳过';
            }
            if (strpos($msg, '成功') !== false) {
                return '成功';
            }
            return '未知';
        }, $command2->infoMessages);

        $this->assertContains('跳过', $results);
        $this->assertContains('成功', $results);
    }

    /**
     * 使用 getKey() 而非硬编码 id, 验证输出包含正确主键值
     */
    public function testUsesGetKeyNotHardcodedId()
    {
        $model = RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        $command = $this->makeCommand();
        $query = RefreshCacheTestModel::noCacheQuery();

        $command->callEachRefreshModelCache($query, true);

        // 验证输出中包含实际主键值
        $this->assertStringContainsString((string) $model->getKey(), $command->infoMessages[0]);
    }

    /**
     * deleteRowCache + resetRowCache 被调用后缓存确实更新
     */
    public function testDeleteAndResetCacheActuallyWork()
    {
        $model = RefreshCacheTestModel::create(['name' => 'Original', 'email' => 'orig@test.com']);

        // 通过 force 刷新建立缓存
        $command1 = $this->makeCommand();
        $command1->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        $cached = RefreshCacheTestModel::findById($model->id);
        $this->assertSame('Original', $cached->name);

        // 修改数据库
        RefreshCacheTestModel::noCacheQuery()->where('id', $model->id)->update(['name' => 'Updated']);

        // 再次 force 刷新缓存
        $command2 = $this->makeCommand();
        $command2->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        // 缓存应该是最新的
        $cachedAfter = RefreshCacheTestModel::findById($model->id);
        $this->assertSame('Updated', $cachedAfter->name);
    }

    /**
     * query clone 应不影响原始 query
     */
    public function testQueryCloneDoesNotAffectOriginal()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);
        RefreshCacheTestModel::create(['name' => 'Bob', 'email' => 'bob@test.com']);

        $query = RefreshCacheTestModel::noCacheQuery();
        $originalSql = $query->toSql();

        $command = $this->makeCommand();
        $command->callEachRefreshModelCache($query, true);

        // 原始 query 不应被修改
        $this->assertSame($originalSql, $query->toSql());
    }

    /**
     * 单条记录 force refresh 后 resetRowCache 验证不应抛异常
     */
    public function testRefreshDoesNotThrowOnSuccess()
    {
        RefreshCacheTestModel::create(['name' => 'Alice', 'email' => 'alice@test.com']);

        $command = $this->makeCommand();

        // 不应抛出 "重置后对比缓存失败" 异常
        $command->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        $this->assertCount(1, $command->infoMessages);
        $this->assertStringContainsString('成功', $command->infoMessages[0]);
    }

    /**
     * 大批量记录应全部处理且计数正确
     */
    public function testLargeBatchProcessing()
    {
        $total = 25;
        for ($i = 1; $i <= $total; $i++) {
            RefreshCacheTestModel::create(['name' => "User{$i}", 'email' => "user{$i}@test.com"]);
        }

        $command = $this->makeCommand();
        $command->callEachRefreshModelCache(RefreshCacheTestModel::noCacheQuery(), true);

        $this->assertCount($total, $command->infoMessages);

        // 验证最后一条消息计数正确
        $lastMsg = $command->infoMessages[$total - 1];
        $this->assertStringContainsString("{$total}/{$total}", $lastMsg);

        // 验证第一条消息计数正确
        $this->assertStringContainsString("1/{$total}", $command->infoMessages[0]);
    }
}

/**
 * 测试用的 Model
 */
class RefreshCacheTestModel extends Model
{
    use SoftDeletes;

    protected $table = 'refresh_cache_test_models';

    protected $fillable = ['name', 'email'];

    public function getCache(): CacheInterface
    {
        return Cache::store('array');
    }

    public function getCacheTtl(?int $duration = null): int
    {
        return 60;
    }

    public function getCachePlaceholder(): ?string
    {
        return md5(__METHOD__);
    }

    public function onChangeRefreshCacheKeys(): array
    {
        return [
            [$this->getKeyName() => $this->getKey()],
        ];
    }
}

/**
 * 测试用的 Command
 */
class RefreshModelCacheTestCommand extends Command
{
    use HasRefreshModelCache;

    protected $signature = 'test:refresh-model-cache';

    /**
     * @var string[]
     */
    public array $infoMessages = [];

    public function info($string, $verbosity = null)
    {
        $this->infoMessages[] = $string;
    }

    public function callEachRefreshModelCache($query, bool $force = true): void
    {
        $this->eachRefreshModelCache($query, $force);
    }
}
