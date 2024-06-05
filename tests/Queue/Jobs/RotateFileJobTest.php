<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\RotateFileJob;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\File;

class RotateFileJobTest extends TestCase
{
    public function testRun()
    {
        $file = sprintf('/tmp/FileRotateJobTest-%s.test.log', md5(1));
        File::put($file, 'test');

        $file = sprintf('/tmp/FileRotateJobTest-%s.test.log', md5(2));
        File::put($file, '');

        try {
            $this->assertJob(RotateFileJob::new([
                'items' => [
                    [
                        'dir'         => ['/tmp/', '/tmp/xxx/'],
                        'pattern'     => 'FileRotateJobTest-*.test.log',
                        'date_format' => date('Y-m-d'),
                    ],
                ],
            ]));
        } finally {
            //File::delete($file);
        }
    }
}
