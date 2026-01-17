<?php

namespace HughCube\Laravel\Knight\Tests\OPcache\Commands;

use HughCube\Laravel\Knight\OPcache\Commands\CompileFilesCommand;
use HughCube\Laravel\Knight\OPcache\OPcache;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Console\OutputStyle;
use Illuminate\Console\Scheduling\Schedule;
use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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

    public function testCompileProcessCodeIncludesOpcacheCompile()
    {
        $command = $this->makeCommand();

        $code = $this->callMethod($command, 'compileProcessCode', ['C:\\tmp\\scripts.json']);

        $this->assertStringContainsString('opcache_compile_file', $code);
        $this->assertStringContainsString('scripts.json', $code);
    }

    public function testGetAppFilesReturnsEmptyWhenDisabled()
    {
        $command = $this->makeCommand(['with-app-files' => 0]);

        $files = $this->callMethod($command, 'getAppFiles');

        $this->assertSame([], $files);
    }

    public function testGetAppFilesIncludesConfig()
    {
        $command = $this->makeCommand(['with-app-files' => 1]);

        $configDir = base_path('config');
        $createdDir = false;

        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
            $createdDir = true;
        }

        $tempFile = $configDir.DIRECTORY_SEPARATOR.'knight_test.php';
        file_put_contents($tempFile, '<?php return [];');

        try {
            $files = array_map('realpath', $this->callMethod($command, 'getAppFiles'));

            $this->assertContains(realpath($tempFile), $files);
        } finally {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
            if ($createdDir && is_dir($configDir)) {
                rmdir($configDir);
            }
        }
    }

    public function testGetComposerFilesReturnsEmptyWhenDisabled()
    {
        $command = $this->makeCommand(['with-composer-files' => 0]);

        $files = $this->callMethod($command, 'getComposerFiles');

        $this->assertSame([], $files);
    }

    public function testGetProdFilesMapsRemoteScripts()
    {
        $property = new ReflectionProperty(OPcache::class, 'instance');
        $property->setAccessible(true);
        $previous = $property->getValue();

        $stub = new class() extends OPcache {
            public function getRemoteScripts($url = null, $timeout = 5, $useAppHost = true): array
            {
                return ['foo.php', 'bar/baz.php'];
            }
        };

        $property->setValue(null, $stub);

        try {
            $command = $this->makeCommand(['with-remote-scripts' => 1]);

            $files = $this->callMethod($command, 'getProdFiles');

            $this->assertContains(base_path('foo.php'), $files);
            $this->assertContains(base_path('bar/baz.php'), $files);
        } finally {
            $property->setValue(null, $previous);
        }
    }

    public function testHandleCompilesFilesWithStubbedProcess()
    {
        $storageDir = storage_path();
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'compile-');
        file_put_contents($tempFile, '<?php echo "ok";');

        $command = new class([$tempFile]) extends CompileFilesCommand {
            public array $messages = [];
            private array $files;

            public function __construct(array $files)
            {
                parent::__construct();
                $this->files = $files;
            }

            protected function compileProcessCode($file): string
            {
                return sprintf(
                    '$dataFile=%s;$classmap=json_decode(file_get_contents($dataFile), true);file_put_contents($dataFile, json_encode([]));',
                    var_export($file, true)
                );
            }

            protected function getAppFiles(): array
            {
                return $this->files;
            }

            protected function getComposerFiles(): array
            {
                return [];
            }

            protected function getProdFiles(): array
            {
                return [];
            }

            protected function getVendorBinFiles(): array
            {
                return [];
            }

            protected function getOctaneFiles(): array
            {
                return [];
            }

            public function info($string, $verbosity = null)
            {
                $this->messages[] = $string;
            }

            public function warn($string, $verbosity = null)
            {
                $this->messages[] = $string;
            }
        };

        $command->setLaravel($this->app);
        $command->setOutput(new OutputStyle(new ArrayInput([]), new BufferedOutput()));

        try {
            $command->handle(new Schedule($this->app));
        } finally {
            if (is_file($tempFile)) {
                unlink($tempFile);
            }
        }

        $this->assertFalse(is_file(storage_path('opcache_compile_files.json')));
        $this->assertTrue(collect($command->messages)->contains(function ($message) {
            return is_string($message) && str_contains($message, 'Successfully compiled');
        }));
    }

    public function testHandleWarnsWhenNoFiles()
    {
        $command = new class() extends CompileFilesCommand {
            public array $messages = [];

            protected function getAppFiles(): array
            {
                return [];
            }

            protected function getComposerFiles(): array
            {
                return [];
            }

            protected function getProdFiles(): array
            {
                return [];
            }

            protected function getVendorBinFiles(): array
            {
                return [];
            }

            protected function getOctaneFiles(): array
            {
                return [];
            }

            public function info($string, $verbosity = null)
            {
                $this->messages[] = $string;
            }

            public function warn($string, $verbosity = null)
            {
                $this->messages[] = $string;
            }
        };

        $command->setLaravel($this->app);
        $command->handle(new Schedule($this->app));

        $this->assertTrue(collect($command->messages)->contains(function ($message) {
            return is_string($message) && str_contains($message, 'No files to compile');
        }));
    }

    private function makeCommand(array $options = []): CompileFilesCommand
    {
        return new class($options) extends CompileFilesCommand {
            private array $options;

            public function __construct(array $options)
            {
                parent::__construct();
                $this->options = $options;
            }

            public function option($key = null)
            {
                return $this->options[$key] ?? null;
            }
        };
    }
}
