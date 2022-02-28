<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\PingAliFcJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class PingAliFcJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(PingAliFcJob::new());
    }
}
