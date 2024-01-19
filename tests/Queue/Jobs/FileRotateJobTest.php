<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\FileRotateJob;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class FileRotateJobTest extends TestCase
{
    public function testRun()
    {
        $file = sprintf('/tmp/FileRotateJobTest-%s.log', md5(Str::random()));

        File::put($file, 'test');

        try {
            $this->assertJob(FileRotateJob::new([
                'items' => [
                    [
                        'dir' => '/tmp/',
                        'pattern' => File::basename($file),
                        'date_format' => date('Y-m-d'),
                    ],
                ],
            ]));
        } finally {
            File::delete($file);
        }
    }
}
