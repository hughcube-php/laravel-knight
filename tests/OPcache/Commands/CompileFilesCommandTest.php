<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18.
 */

namespace HughCube\Laravel\Knight\Tests\OPcache\Commands;

use HughCube\Laravel\Knight\Tests\TestCase;

class CompileFilesCommandTest extends TestCase
{
    public function testRun()
    {
        if (!extension_loaded('Zend OPcache')) {
            $this->markTestSkipped('OPcache extension is not loaded');
            return;
        }

        $this->artisan('opcache:compile-files')->assertExitCode(0);
    }
}
