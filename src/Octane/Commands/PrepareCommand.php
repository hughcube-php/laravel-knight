<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/8/24
 * Time: 14:44
 */

namespace HughCube\Laravel\Knight\Octane\Commands;

use Laravel\Octane\Commands\StartSwooleCommand;
use Laravel\Octane\Swoole\ServerProcessInspector;
use Laravel\Octane\Swoole\ServerStateFile;
use Laravel\Octane\Swoole\SwooleExtension;

class PrepareCommand extends StartSwooleCommand
{
    /**
     * The command's signature.
     *
     * @var string
     */
    public $signature = 'octane:prepare
                    {--host=127.0.0.1 : The IP address the server should bind to}
                    {--port=8000 : The port the server should be available on}
                    {--workers=auto : The number of workers that should be available to handle requests}
                    {--task-workers=auto : The number of task workers that should be available to handle tasks}
                    {--max-requests=500 : The number of requests to process before reloading the server}
                    {--watch : Automatically reload the server when the application is modified}';

    /**
     * The command's description.
     *
     * @var string
     */
    public $description = 'Prepare the Octane Swoole server';

    public function handle(
        ServerProcessInspector $inspector,
        ServerStateFile $serverStateFile,
        SwooleExtension $extension
    ) {
        $this->writeServerStateFile($serverStateFile, $extension);
        $this->info(sprintf("file writing succeeded: %s", $serverStateFile->path()));
    }
}
