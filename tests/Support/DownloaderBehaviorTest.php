<?php

namespace HughCube\Laravel\Knight\Tests\Support;

use BadMethodCallException;
use HughCube\Laravel\Knight\Support\Downloader;
use HughCube\Laravel\Knight\Tests\TestCase;

class DownloaderBehaviorTest extends TestCase
{
    public function testCallUnknownMethodThrows()
    {
        $this->expectException(BadMethodCallException::class);

        Downloader::instance()->unknownMethod();
    }

    public function testCallStaticUnknownMethodThrows()
    {
        $this->expectException(BadMethodCallException::class);

        Downloader::unknownStatic();
    }
}
