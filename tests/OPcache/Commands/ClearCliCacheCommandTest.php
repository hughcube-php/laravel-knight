<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18.
 */

namespace HughCube\Laravel\Knight\Tests\OPcache\Commands;

use HughCube\Laravel\Knight\Tests\TestCase;

class ClearCliCacheCommandTest extends TestCase
{
    public function testRun()
    {
        $this->markTestSkipped();

        $this->artisan('opcache:clear-cli-cache')->assertExitCode(0);
    }
}
