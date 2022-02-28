<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Queue\Jobs\AutoCleanFileJob;
use HughCube\Laravel\Knight\Support\Carbon;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\File;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;

class AutoCleanFileJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(AutoCleanFileJob::new());
    }
}
