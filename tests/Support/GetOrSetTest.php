<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 23:38
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\GetOrSet;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Str;
use ReflectionException;

class GetOrSetTest extends TestCase
{
    /**
     * @return void
     * @throws ReflectionException
     */
    public function testGetOrSetNotEmpty()
    {
        $mock = $this->getMockForTrait(GetOrSet::class);

        $values = [];
        $key = Str::random();
        for ($i = 1; $i <= 10; $i++) {
            $values[] = $this->callMethod($mock, 'getOrSet', [
                $key,
                function () {
                    return Str::random();
                }
            ]);
        }
        $values = array_values(array_unique($values));
        $this->assertCount(1, $values);
        $this->assertSame(16, strlen($values[0]));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testGetOrSetEmpty()
    {
        $mock = $this->getMockForTrait(GetOrSet::class);

        $key = Str::random();

        $this->assertNull($this->callMethod($mock, 'getOrSet', [
            $key, function () {
                return null;
            }
        ]));
        $this->assertNull($this->callMethod($mock, 'getOrSet', [
            $key, function () {
                return Str::random();
            }
        ]));
        $this->assertNull($this->callMethod($mock, 'getOrSet', [
            $key, function () {
                return Str::random();
            }
        ]));
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testFlushHughCubeKnightClassSelfCacheStorage()
    {
        $mock = $this->getMockForTrait(GetOrSet::class);

        $value = $this->callMethod($mock, 'getOrSet', [
            Str::random(), function () {
                return Str::random();
            }
        ]);
        $this->callMethod($mock, 'flushHughCubeKnightClassSelfCacheStorage');
        $this->assertNotSame($value, $this->callMethod($mock, 'getOrSet', [
            Str::random(), function () {
                return Str::random();
            }
        ]));
    }
}
