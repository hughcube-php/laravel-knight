<?php

namespace HughCube\Laravel\Knight\Tests\OPcache\Jobs;

use HughCube\Laravel\Knight\OPcache\Jobs\WatchFilesJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class WatchFilesJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(new WatchFilesJob());
    }
}
