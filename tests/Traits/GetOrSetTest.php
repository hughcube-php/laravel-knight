<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 23:38.
 */

namespace HughCube\Laravel\Knight\Tests\Traits;

use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\Laravel\Knight\Traits\GetOrSet;
use Illuminate\Support\Str;
use ReflectionException;

class GetOrSetTest extends TestCase
{
    /**
     * @throws ReflectionException
     *
     * @return void
     */
    public function testGetOrSetNotEmpty()
    {
        $mock = new class() {
            use GetOrSet;
        };

        $values = [];
        $key = Str::random();
        for ($i = 1; $i <= 10; $i++) {
            $values[] = $this->callMethod($mock, 'getOrSet', [
                $key,
                function () {
                    return Str::random();
                },
            ]);
        }
        $values = array_values(array_unique($values));
        $this->assertCount(1, $values);
        $this->assertSame(16, strlen($values[0]));
    }

    /**
     * @throws ReflectionException
     *
     * @return void
     */
    public function testGetOrSetEmpty()
    {
        $mock = new class() {
            use GetOrSet;
        };

        $key = Str::random();

        $this->assertNull($this->callMethod($mock, 'getOrSet', [
            $key,
            function () {
                return null;
            },
        ]));
        $this->assertNull($this->callMethod($mock, 'getOrSet', [
            $key,
            function () {
                return Str::random();
            },
        ]));
        $this->assertNull($this->callMethod($mock, 'getOrSet', [
            $key,
            function () {
                return Str::random();
            },
        ]));
    }

    /**
     * @throws ReflectionException
     *
     * @return void
     */
    public function testFlushHughCubeKnightClassSelfCacheStorage()
    {
        $mock = new class() {
            use GetOrSet;
        };

        $value = $this->callMethod($mock, 'getOrSet', [
            Str::random(), function () {
                return Str::random();
            },
        ]);
        $this->callMethod($mock, 'flushHughCubeKnightClassSelfCacheStorage');
        $this->assertNotSame($value, $this->callMethod($mock, 'getOrSet', [
            Str::random(), function () {
                return Str::random();
            },
        ]));
    }
}
