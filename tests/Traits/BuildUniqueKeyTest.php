<?php

namespace HughCube\Laravel\Knight\Tests\Traits;

use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\Laravel\Knight\Traits\BuildUniqueKey;

class BuildUniqueKeyTest extends TestCase
{
    public function testBuildUniqueKeyIsDeterministic()
    {
        $data = ['a' => 1, 'b' => 2];
        $full = self::callMethod(TestBuildUniqueKey::class, 'buildUniqueKey', [$data]);

        $this->assertSame($full, self::callMethod(TestBuildUniqueKey::class, 'buildUniqueKey', [$data]));
        $this->assertSame(8, strlen(self::callMethod(TestBuildUniqueKey::class, 'buildUniqueKey', [$data, 8])));
    }

    public function testBuildUniqueCacheKeyUsesPrefix()
    {
        $key = self::callMethod(TestBuildUniqueKey::class, 'buildUniqueCacheKey', ['prefix', 'value', 6]);

        $this->assertStringStartsWith('prefix_', $key);
        $this->assertSame(strlen('prefix_') + 6, strlen($key));
    }
}

class TestBuildUniqueKey
{
    use BuildUniqueKey;
}
