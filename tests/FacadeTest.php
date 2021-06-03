<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 11:45 下午.
 */

namespace HughCube\Laravel\Package\Tests;

use HughCube\Laravel\Package\Facade;
use HughCube\Laravel\Package\Manager;

class FacadeTest extends TestCase
{
    public function testIsFacade()
    {
        $this->assertInstanceOf(Manager::class, Facade::getFacadeRoot());
    }
}
