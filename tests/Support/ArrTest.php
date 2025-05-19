<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/18
 * Time: 19:11.
 */

namespace HughCube\Laravel\Knight\Tests\Support;

use HughCube\Laravel\Knight\Support\Arr;
use HughCube\Laravel\Knight\Tests\TestCase;

class ArrTest extends TestCase
{
    public function testGetCombos()
    {
        $fields = ['bank_code', 'name', 'id_code', 'phone'];

        $this->assertSame(11, count(Arr::getCombos($fields)));
    }
}
