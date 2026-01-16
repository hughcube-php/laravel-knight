<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/26
 * Time: 20:12.
 */

namespace HughCube\Laravel\Knight\Tests\Console\Commands;

use HughCube\Laravel\Knight\Tests\TestCase;
use Symfony\Component\VarDumper\VarDumper;

class ConfigTest extends TestCase
{
    /**
     * @return void
     */
    public function testRun()
    {
        $previousHandler = VarDumper::setHandler(static function ($var, $label = null) {
            // Silence VarDumper output for this test.
        });

        try {
            $this->artisan('knight:config')->assertExitCode(0);
        } finally {
            VarDumper::setHandler($previousHandler);
        }
    }
}
