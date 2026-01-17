<?php

namespace HughCube\Laravel\Knight\Tests\Octane;

use HughCube\Laravel\Knight\Octane\Octane;
use HughCube\Laravel\Knight\Tests\TestCase;

class OctaneTest extends TestCase
{
    public function testCanInstantiateOctane()
    {
        $this->assertInstanceOf(Octane::class, new Octane());
    }
}
