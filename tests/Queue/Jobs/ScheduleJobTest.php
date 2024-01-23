<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\CleanFilesJob;
use HughCube\Laravel\Knight\Queue\Jobs\ScheduleJob;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Str;

class ScheduleJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(CleanFilesJob::new([
            'items' => [],
        ]));
    }

    public function testIsDue()
    {
        $job = new class() extends ScheduleJob {
            protected function testHandler()
            {
                $this->isDue('* * * * *');
            }
        };

        $this->assertJob($job);
    }
}
