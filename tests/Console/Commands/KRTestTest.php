<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/26
 * Time: 20:21
 */

namespace HughCube\Laravel\Knight\Tests\Console\Commands;

use HughCube\Laravel\Knight\Tests\TestCase;

class KRTestTest extends TestCase
{
    public function testRun()
    {
        $this->artisan('krtest', ['--class' => KRTest::class])->assertExitCode(0);
    }
}
