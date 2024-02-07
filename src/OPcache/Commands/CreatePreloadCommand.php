<?php

namespace HughCube\Laravel\Knight\OPcache\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class CreatePreloadCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'opcache:create-preload
                    {--with_remote_scripts=0 }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create the php preload file.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $process = new Process(
            $this->serverCommand(),
            public_path(),
            [
                'WITH_REMOTE_SCRIPTS' => strval(intval($this->option('with_remote_scripts')))
            ]
        );

        $process->mustRun(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo 'ERR > '.$buffer;
            } else {
                echo 'OUT > '.$buffer;
            }
        });
    }

    /**
     * Get the full server command.
     *
     * @return array
     */
    protected function serverCommand(): array
    {
        $server = file_exists(base_path('create_preload.php'))
            ? base_path('create_preload.php')
            : __DIR__.'/../../../resources/create_preload.php';

        return [
            (new PhpExecutableFinder())->find(false),
            $server,
        ];
    }
}
