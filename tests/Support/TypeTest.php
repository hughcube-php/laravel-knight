<?php

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Type;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Collection;

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

    public function testFloat()
    {
        $this->assertNull(Type::float(null));
        $this->assertSame(1.0, Type::float(1));
        $this->assertSame(1.5, Type::float('1.5'));
        $this->assertSame(0.0, Type::float('0'));
        $this->assertSame(0.0, Type::float(0));
    }

    public function testString()
    {
        $this->assertNull(Type::string(null));
        $this->assertSame('1', Type::string(1));
        $this->assertSame('1', Type::string('1'));
        $this->assertSame('0', Type::string('0'));
        $this->assertSame('0', Type::string(0));
    }

    public function testBool()
    {
        $this->assertNull(Type::bool(null));
        $this->assertSame(true, Type::bool(1));
        $this->assertSame(false, Type::bool(0));
        $this->assertSame(true, Type::bool('1'));
        $this->assertSame(false, Type::bool(''));
    }

    public function testCollection()
    {
        $this->assertNull(Type::collection(null));
        $this->assertInstanceOf(Collection::class, Type::collection([1, 2]));
        $this->assertEquals([1, 2], Type::collection([1, 2])->all());
        $this->assertInstanceOf(Collection::class, Type::collection('foo'));
    }
}
