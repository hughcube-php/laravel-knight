<?php

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Type;
use HughCube\Laravel\Knight\Tests\TestCase;

class TypeTest extends TestCase
{
    public function testInt()
    {
        $this->assertNull(Type::int(null));
        $this->assertSame(1, Type::int(1));
        $this->assertSame(1, Type::int('1'));
        $this->assertSame(0, Type::int('0'));
        $this->assertSame(0, Type::int(0));
    }

    public function testString()
    {
        $this->assertNull(Type::string(null));
        $this->assertSame('1', Type::string(1));
        $this->assertSame('1', Type::string('1'));
        $this->assertSame('0', Type::string('0'));
        $this->assertSame('0', Type::string(0));
    }
}
