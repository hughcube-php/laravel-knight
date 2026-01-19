<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\AutoCleanFileJob;
use HughCube\Laravel\Knight\Tests\TestCase;

class AutoCleanFileJobTest extends TestCase
{
    public function testDeletesExpiredFiles()
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'knight_autoclean_'.uniqid();
        mkdir($dir, 0777, true);

        $oldFile = $dir.DIRECTORY_SEPARATOR.'old.log';
        $newFile = $dir.DIRECTORY_SEPARATOR.'new.log';
        file_put_contents($oldFile, 'old');
        file_put_contents($newFile, 'new');

        touch($oldFile, time() - 2 * 86400);
        touch($newFile, time());

        try {
            $job = new AutoCleanFileJob([
<<<<<<< HEAD
                'dir' => $dir,
                'pattern' => '*.log',
=======
                'dir'      => $dir,
                'pattern'  => '*.log',
>>>>>>> 8f22473b86b48b69738e0e53f6652b3510bd616f
                'max_days' => 1,
            ]);
            $job->handle();

            $this->assertFileDoesNotExist($oldFile);
            $this->assertFileExists($newFile);
        } finally {
            @unlink($oldFile);
            @unlink($newFile);
            @rmdir($dir);
        }
    }
}
