<?php

namespace HughCube\Laravel\Knight\Tests\Listeners;

use HughCube\Laravel\Knight\Listeners\LogPid;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use stdClass;

class LogPidTest extends TestCase
{
    public function testHandleLogsPidInfo()
    {
        Log::spy();

        $listener = new LogPid();
        $listener->handle(new stdClass());

        $hostname = gethostname();
        $pid = getmypid();

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function ($message) use ($hostname, $pid) {
                return is_string($message)
                    && str_contains($message, 'Process pid, event: stdClass')
                    && str_contains($message, 'hostname: '.$hostname)
                    && str_contains($message, 'pid: '.$pid);
            });
    }
}
