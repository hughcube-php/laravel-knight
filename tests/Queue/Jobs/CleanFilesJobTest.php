<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\AutoCleanFileJob;
use HughCube\Laravel\Knight\Queue\Jobs\CleanFilesJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class CleanFilesJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(CleanFilesJob::new());
    }
}
