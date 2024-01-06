<?php

namespace HughCube\Laravel\Knight\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class CreatePreload extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'knight:create-preload';

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
        $process = new Process($this->serverCommand(), public_path());

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
    protected function serverCommand()
    {
        $server = file_exists(public_path('create_preload.php'))
            ? public_path('create_preload.php')
            : __DIR__.'/../../../resources/create_preload.php';

        return [
            (new PhpExecutableFinder)->find(false),
            $server,
        ];
    }
}
