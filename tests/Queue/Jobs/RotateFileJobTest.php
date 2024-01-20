<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\RotateFileJob;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\File;

class RotateFileJobTest extends TestCase
{
    public function testRun()
    {
        $file = sprintf('/tmp/FileRotateJobTest-%s.log', md5(1));

        File::put($file, 'test');

        try {
            $this->assertJob(RotateFileJob::new([
                'items' => [
                    [
                        'dir'         => '/tmp/',
                        'pattern'     => File::basename($file),
                        'date_format' => date('Y-m-d'),
                    ],
                ],
            ]));
        } finally {
            //File::delete($file);
        }
    }
}
