<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\BatchPingJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class BatchPingJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(BatchPingJob::new([
            'jobs' => [
                ['url' => null],
                ['url' => 'https://www1111111111.baidu.com/'],
            ],
        ]));
    }
}
