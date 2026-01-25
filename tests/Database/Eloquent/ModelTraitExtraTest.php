<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ModelTraitExtraTest extends TestCase
{
    // ==================== PostgreSQL Array Helpers Tests ====================

    public function testParsePgArrayEmpty()
    {
        $user = new User();

        $result = self::callMethod($user, 'parsePgArray', ['']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

        $result = self::callMethod($user, 'parsePgArray', ['{}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

        $result = self::callMethod($user, 'parsePgArray', ['  {}  ']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function testParsePgArrayNumeric()
    {
        $user = new User();

        // 简单整数数组
        $result = self::callMethod($user, 'parsePgArray', ['{1,2,3}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['1', '2', '3'], $result->all());

        // 带空格
        $result = self::callMethod($user, 'parsePgArray', ['{ 1, 2, 3 }']);
        $this->assertSame(['1', '2', '3'], $result->all());

        // 单个元素
        $result = self::callMethod($user, 'parsePgArray', ['{42}']);
        $this->assertSame(['42'], $result->all());

        // 浮点数
        $result = self::callMethod($user, 'parsePgArray', ['{1.5,2.5,3.5}']);
        $this->assertSame(['1.5', '2.5', '3.5'], $result->all());
    }

    public function testParsePgArrayText()
    {
        $user = new User();

        // 带引号的文本数组
        $result = self::callMethod($user, 'parsePgArray', ['{"a","b","c"}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['a', 'b', 'c'], $result->all());

        // 混合内容
        $result = self::callMethod($user, 'parsePgArray', ['{"hello","world"}']);
        $this->assertSame(['hello', 'world'], $result->all());

        // 包含空格的文本
        $result = self::callMethod($user, 'parsePgArray', ['{"hello world","foo bar"}']);
        $this->assertSame(['hello world', 'foo bar'], $result->all());
    }

    public function testParsePgArrayWithNull()
    {
        $user = new User();

        // 包含 NULL 值
        $result = self::callMethod($user, 'parsePgArray', ['{1,NULL,3}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['1', null, '3'], $result->all());

        $result = self::callMethod($user, 'parsePgArray', ['{"a",NULL,"c"}']);
        $this->assertSame(['a', null, 'c'], $result->all());

        // 全部是 NULL
        $result = self::callMethod($user, 'parsePgArray', ['{NULL,NULL}']);
        $this->assertSame([null, null], $result->all());
    }

    public function testParsePgArrayWithEscapedChars()
    {
        $user = new User();

        // 包含转义引号
        $result = self::callMethod($user, 'parsePgArray', ['{"he said \\"hello\\"","world"}']);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(['he said "hello"', 'world'], $result->all());

        // 包含转义反斜杠
        $result = self::callMethod($user, 'parsePgArray', ['{"path\\\\to\\\\file","other"}']);
        $this->assertSame(['path\\to\\file', 'other'], $result->all());
    }

    public function testFormatPgArrayEmpty()
    {
        $user = new User();

        $this->assertSame('{}', self::callMethod($user, 'formatPgArray', [[]]));
    }

    public function testFormatPgArrayNumeric()
    {
        $user = new User();

        // 整数数组
        $result = self::callMethod($user, 'formatPgArray', [[1, 2, 3]]);
        $this->assertSame('{1,2,3}', $result);

        // 浮点数数组
        $result = self::callMethod($user, 'formatPgArray', [[1.5, 2.5, 3.5]]);
        $this->assertSame('{1.5,2.5,3.5}', $result);
    }

    public function testFormatPgArrayText()
    {
        $user = new User();

        // 字符串数组
        $result = self::callMethod($user, 'formatPgArray', [['a', 'b', 'c']]);
        $this->assertSame('{"a","b","c"}', $result);

        // 包含空格
        $result = self::callMethod($user, 'formatPgArray', [['hello world', 'foo bar']]);
        $this->assertSame('{"hello world","foo bar"}', $result);
    }

    public function testFormatPgArrayWithNull()
    {
        $user = new User();

        $result = self::callMethod($user, 'formatPgArray', [[1, null, 3]]);
        $this->assertSame('{1,NULL,3}', $result);

        $result = self::callMethod($user, 'formatPgArray', [['a', null, 'c']]);
        $this->assertSame('{"a",NULL,"c"}', $result);
    }

    public function testFormatPgArrayWithBoolean()
    {
        $user = new User();

        $result = self::callMethod($user, 'formatPgArray', [[true, false, true]]);
        $this->assertSame('{true,false,true}', $result);
    }

    public function testFormatPgArrayWithSpecialChars()
    {
        $user = new User();

        // 包含引号
        $result = self::callMethod($user, 'formatPgArray', [['he said "hello"', 'world']]);
        $this->assertSame('{"he said \\"hello\\"","world"}', $result);

        // 包含反斜杠
        $result = self::callMethod($user, 'formatPgArray', [['path\\to\\file', 'other']]);
        $this->assertSame('{"path\\\\to\\\\file","other"}', $result);
    }

    public function testParsePgArrayRoundTrip()
    {
        $user = new User();

        // 整数往返
        $original = [1, 2, 3];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertInstanceOf(Collection::class, $parsed);
        $this->assertSame(['1', '2', '3'], $parsed->all());

        // 字符串往返
        $original = ['hello', 'world'];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());

        // 特殊字符往返
        $original = ['he said "hello"', 'path\\to\\file'];
        $formatted = self::callMethod($user, 'formatPgArray', [$original]);
        $parsed = self::callMethod($user, 'parsePgArray', [$formatted]);
        $this->assertSame($original, $parsed->all());
    }

    public function testParsePgArrayCollectionMethods()
    {
        $user = new User();

        // 测试 Collection 方法可用
        $result = self::callMethod($user, 'parsePgArray', ['{1,2,3,4,5}']);
        $this->assertInstanceOf(Collection::class, $result);

        // filter
        $filtered = $result->filter(function ($value) {
            return intval($value) > 2;
        })->values();
        $this->assertSame(['3', '4', '5'], $filtered->all());

        // map
        $mapped = $result->map(function ($value) {
            return intval($value) * 2;
        });
        $this->assertSame([2, 4, 6, 8, 10], $mapped->all());

        // first/last
        $this->assertSame('1', $result->first());
        $this->assertSame('5', $result->last());
    }

    // ==================== Existing Tests ====================

    public function testCacheHelpers()
    {
        $user = new User();

        $placeholder = $user->getCachePlaceholder();
        $this->assertNotNull($placeholder);
        $this->assertTrue($user->hasCachePlaceholder());
        $this->assertTrue($user->isCachePlaceholder($placeholder));
        $this->assertFalse($user->isCachePlaceholder($placeholder.'-other'));

        $this->assertSame('m1', $user->getModelCachePrefix());
        $this->assertSame('v1', $user->getCacheVersion());
        $this->assertSame(10, $user->getCacheTtl());

        $this->assertFalse($user->isFromCache());
        $this->assertSame($user, $user->setIsFromCache());
        $this->assertTrue($user->isFromCache());
        $this->assertSame($user, $user->setIsFromCache(false));
        $this->assertFalse($user->isFromCache());

        $user->id = 1;
        $user->nickname = 'neo';
        $this->assertSame(
            [
                ['id' => 1],
                ['nickname' => 'neo'],
            ],
            $user->onChangeRefreshCacheKeys()
        );
    }

    public function testGetSetColumnCollection()
    {
        $user = new User();
        $user->tags = 'admin,editor,,admin,';

        $collection = $user->getSetColumnCollection('tags');
        $this->assertSame(['admin', 'editor'], $collection->all());

        $filtered = $user->getSetColumnCollection('tags', ',', function ($value) {
            return $value !== '' && $value !== 'editor';
        });
        $this->assertSame(['admin'], $filtered->all());

        $user->roles = 'a|b|a';
        $this->assertSame(['a', 'b'], $user->getSetColumnCollection('roles', '|')->all());
    }

    public function testVersionAndSortHelpers()
    {
        $now = Carbon::create(2025, 1, 1, 0, 0, 0);
        Carbon::setTestNow($now);

        try {
            $user = new User();
            $this->assertSame($now->getTimestamp() - 1660899108, $user->genDefaultSort());
        } finally {
            Carbon::setTestNow();
        }

        $version = User::genModelVersion();
        $this->assertIsInt($version);
        $this->assertGreaterThanOrEqual(0, $version);

        $user = new User();
        $user->resetModelVersion();
        $this->assertIsInt($user->data_version);
        $this->assertGreaterThanOrEqual(0, $user->data_version);
    }

    public function testMakeColumnsCacheKeyIsDeterministic()
    {
        $user = new User();

        $keyId = $user->makeColumnsCacheKey(['id' => 5]);
        $keyNumeric = $user->makeColumnsCacheKey([5]);
        $this->assertSame($keyId, $keyNumeric);

        $this->assertNotSame($keyId, $user->makeColumnsCacheKey(['id' => 6]));

        $keyOrderA = $user->makeColumnsCacheKey(['id' => 5, 'nickname' => 'neo']);
        $keyOrderB = $user->makeColumnsCacheKey(['nickname' => 'neo', 'id' => 5]);
        $this->assertSame($keyOrderA, $keyOrderB);
    }

    public function testJson2ArrayAndEqualityHelpers()
    {
        $user = new User();

        $this->assertSame([], self::callMethod($user, 'json2Array', ['', false]));
        $this->assertSame(['a', '', 'b'], self::callMethod($user, 'json2Array', ['["a","", "b"]', false]));
        $filtered = self::callMethod($user, 'json2Array', ['["a","", "b"]', true]);
        $this->assertSame(['a', 'b'], array_values($filtered));

        $filtered = self::callMethod($user, 'json2Array', ['["a","bb","ccc"]', function ($value) {
            return strlen($value) > 1;
        }]);
        $this->assertSame(['bb', 'ccc'], array_values($filtered));

        $userA = new User();
        $userA->setRawAttributes(['id' => 1, 'nickname' => 'neo']);

        $userB = new User();
        $userB->setRawAttributes(['id' => 1, 'nickname' => 'neo']);
        $this->assertTrue($userA->equal($userB));

        $userB->setRawAttributes(['id' => 1, 'nickname' => 'trinity']);
        $this->assertFalse($userA->equal($userB));

        $userC = new User();
        $userC->setRawAttributes(['id' => 1]);
        $this->assertFalse($userA->equal($userC));

        $this->assertSame($userA, $userA->ifReturnSelf(true));
        $this->assertNull($userA->ifReturnSelf(false));

        $deleted = new User();
        $deleted->deleted_at = Carbon::now();
        $this->assertNull($deleted->ifAvailableReturnSelf());
        $this->assertTrue(User::isAvailableModel($userA));
        $this->assertFalse(User::isAvailableModel($deleted));
    }
}
