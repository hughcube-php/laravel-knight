<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/26
 * Time: 20:19.
 */

namespace HughCube\Laravel\Knight\Tests\Console\Commands;

use HughCube\Laravel\Knight\Tests\TestCase;

class EnvironmentTest extends TestCase
{
    public function testRun()
    {
        $this->artisan('knight:env')->assertExitCode(0);
    }
}
