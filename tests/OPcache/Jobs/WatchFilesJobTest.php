<?php

namespace HughCube\Laravel\Knight\Tests\OPcache\Jobs;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use HughCube\Laravel\Knight\OPcache\Jobs\WatchFilesJob;
use HughCube\Laravel\Knight\Support\HttpClient;
use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\PUrl\Url as PUrl;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use JetBrains\PhpStorm\ArrayShape;
use Throwable;

class WatchFilesJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(new WatchFilesJob());
    }
}
