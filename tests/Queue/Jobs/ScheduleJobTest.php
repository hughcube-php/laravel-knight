<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\CleanFilesJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class ScheduleJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(CleanFilesJob::new([
            'items' => [],
        ]));
    }
}
