<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\PingDatabaseJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class PingDatabaseJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(PingDatabaseJob::new());
    }
}
