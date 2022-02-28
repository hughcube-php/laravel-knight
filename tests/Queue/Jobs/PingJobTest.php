<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\PingJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class PingJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(PingJob::new());
    }
}
