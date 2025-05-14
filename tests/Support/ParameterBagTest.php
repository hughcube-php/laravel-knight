<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/1
 * Time: 14:36.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Tests\TestCase;

class ParameterBagTest extends TestCase
{
    public function testMake()
    {
        $this->assertNoException(function () {
            $this->app->make(ParameterBag::class);
        });
    }
}
