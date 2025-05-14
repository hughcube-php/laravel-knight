<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/26
 * Time: 20:12.
 */

namespace HughCube\Laravel\Knight\Tests\Console\Commands;

use HughCube\Laravel\Knight\Tests\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @return void
     */
    public function testRun()
    {
        $this->artisan('knight:config')->assertExitCode(0);
    }
}
