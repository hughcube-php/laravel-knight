<?php

namespace HughCube\Laravel\Knight\Tests\OPcache\Commands;

use HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand;
use HughCube\Laravel\Knight\Tests\TestCase;

class CompileFilesCommandHelperTest extends TestCase
{
    public function testFindPhpScriptsIncludesShebangFiles()
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'knight_opcache_scripts_'.uniqid();
        mkdir($dir, 0777, true);

        $phpFile = $dir.DIRECTORY_SEPARATOR.'a.php';
        $shebangFile = $dir.DIRECTORY_SEPARATOR.'b';
        $textFile = $dir.DIRECTORY_SEPARATOR.'c.txt';

        file_put_contents($phpFile, "<?php echo 'a';");
        file_put_contents($shebangFile, "#!/usr/bin/env php\n<?php echo 'b';");
        file_put_contents($textFile, "nope");

        try {
            $command = new CompileFilesCommand();
            $scripts = $this->callMethod($command, 'findPhpScripts', [$dir, false]);

            $this->assertContains(realpath($phpFile), $scripts);
            $this->assertContains(realpath($shebangFile), $scripts);
            $this->assertNotContains(realpath($textFile), $scripts);
        } finally {
            @unlink($phpFile);
            @unlink($shebangFile);
            @unlink($textFile);
            @rmdir($dir);
        }
    }
}
