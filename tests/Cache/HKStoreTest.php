<?php

namespace HughCube\Laravel\Knight\Tests\Cache;

use DateInterval;
use HughCube\Laravel\Knight\Cache\HKStore;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Carbon;

class HKStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testSetGetHasDeleteAndClear()
    {
        $store = new HKStore();

        $this->assertFalse($store->has('missing'));
        $this->assertSame('default', $store->get('missing', 'default'));

        $store->set('foo', 'bar');
        $this->assertTrue($store->has('foo'));
        $this->assertSame('bar', $store->get('foo'));

        $store->delete('foo');
        $this->assertFalse($store->has('foo'));

        $store->set('a', 1);
        $store->set('b', 2);
        $store->clear();

        $this->assertFalse($store->has('a'));
        $this->assertFalse($store->has('b'));
    }

    public function testGetMultipleSetMultipleDeleteMultiple()
    {
        $store = new HKStore();

        $store->setMultiple(['a' => 1, 'b' => 2]);

        $this->assertSame(
            ['a' => 1, 'b' => 2, 'c' => 'fallback'],
            $store->getMultiple(['a', 'b', 'c'], 'fallback')
        );

        $store->deleteMultiple(['a', 'b']);
        $this->assertSame(
            ['a' => 'fallback', 'b' => 'fallback'],
            $store->getMultiple(['a', 'b'], 'fallback')
        );
    }

    public function testExpirationAndGc()
    {
        $store = new HKStore();
        $now = Carbon::create(2024, 1, 1, 0, 0, 0, 'UTC');
        Carbon::setTestNow($now);

        $store->set('short', 'value', 1);
        $store->set('interval', 'value2', new DateInterval('PT5S'));
        $store->set('long', 'value3', 10);

        $this->assertTrue($store->has('short'));
        $this->assertTrue($store->has('interval'));
        $this->assertTrue($store->has('long'));

        Carbon::setTestNow($now->copy()->addSeconds(2));

        $this->assertFalse($store->has('short'));
        $this->assertTrue($store->has('interval'));
        $this->assertTrue($store->has('long'));

        $store->gc();

        $this->assertFalse($store->has('short'));
        $this->assertTrue($store->has('interval'));
        $this->assertTrue($store->has('long'));

        Carbon::setTestNow($now->copy()->addSeconds(6));

        $this->assertFalse($store->has('interval'));
        $this->assertTrue($store->has('long'));
    }

    public function testGetOrSet()
    {
        $store = new HKStore();
        $calls = 0;

        $value = $store->getOrSet('key', function () use (&$calls) {
            $calls++;

            return 'value';
        });

        $this->assertSame('value', $value);
        $this->assertSame(1, $calls);

        $value = $store->getOrSet('key', function () use (&$calls) {
            $calls++;

            return 'value2';
        });

        $this->assertSame('value', $value);
        $this->assertSame(1, $calls);
    }
}
