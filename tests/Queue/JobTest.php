<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 2:12 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Queue;

use Closure;
use Exception;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Tests\TestCase;
use Symfony\Component\Console\Output\ConsoleOutput;

class JobTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testWriteLog()
    {
        /** @var Job $job */
        $job = new class() extends Job {
            protected function action(): void
            {
            }
        };

        $getOutput = Closure::bind(function () {
            /** @var Job $this */
            return app()->make(ConsoleOutput::class);
        }, $job, Job::class);

        $uuid = md5(random_bytes(100));
        $getOutput()->writeln("<info>$uuid</info>");

        $this->assertTrue(true);
    }
}
