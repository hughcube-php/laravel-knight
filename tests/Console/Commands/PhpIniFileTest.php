<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/26
 * Time: 20:20.
 */

namespace HughCube\Laravel\Knight\Tests\Console\Commands;

use HughCube\Laravel\Knight\Tests\TestCase;

class PhpIniFileTest extends TestCase
{
    public function testRun()
    {
        $this->artisan('knight:php-ini-file')->assertExitCode(0);
    }
}
