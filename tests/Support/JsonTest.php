<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:51.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Json;
use HughCube\Laravel\Knight\Tests\TestCase;

class JsonTest extends TestCase
{
    public function testDecode()
    {
        $this->assertSame([], Json::decode('[]', true));
        $this->assertSame([], Json::decode('{}', true));
        $this->assertTrue(Json::decode('{}') instanceof \stdClass);
        $this->assertSame(null, Json::decode(''));
    }

    public function testDecodeArray()
    {
        $this->assertSame([], Json::decodeArray('[]'));
        $this->assertSame([], Json::decodeArray('{}'));
        $this->assertSame(null, Json::decodeArray(''));
    }

    public function testDecodeObject()
    {
        $this->assertNull(Json::decodeObject('[]'));
        $this->assertTrue(Json::decodeObject('{}') instanceof \stdClass);
        $this->assertSame(null, Json::decodeObject(''));
    }
}
