<?php

namespace HughCube\Laravel\Knight\Tests\Database\Eloquent;

use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Carbon;

class ModelTraitExtraTest extends TestCase
{
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
        $this->assertTrue($userA->isEqualAttributes($userB));
        $this->assertTrue($userA->equal($userB));

        $userB->setRawAttributes(['id' => 1, 'nickname' => 'trinity']);
        $this->assertFalse($userA->isEqualAttributes($userB));
        $this->assertFalse($userA->equal($userB));

        $userC = new User();
        $userC->setRawAttributes(['id' => 1]);
        $this->assertFalse($userA->isEqualAttributes($userC));
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
