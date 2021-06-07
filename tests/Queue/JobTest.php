<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 2:12 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Queue;

use Closure;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Tests\TestCase;

class JobTest extends TestCase
{
    public function testWriteLog()
    {
        /** @var Job $job */
        $job = new Job([]);

        $getOutput = Closure::bind(function () {
            /** @var Job $this */
            return $this->getOutput();
        }, $job, Job::class);

        $uuid = md5(random_bytes(100));
        $getOutput()->writeln("<info>$uuid</info>");

        $this->assertTrue(true);
    }
}
