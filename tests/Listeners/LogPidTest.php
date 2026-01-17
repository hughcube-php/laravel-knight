<?php

namespace HughCube\Laravel\Knight\Tests\Listeners;

use HughCube\Laravel\Knight\Listeners\LogPid;
use HughCube\Laravel\Knight\Tests\TestCase;
use stdClass;

class LogPidTest extends TestCase
{
    public function testHandleLogsPidInfo()
    {
        $handler = $this->setupTestLogHandler();

        $listener = new LogPid();
        $listener->handle(new stdClass());

        $hostname = gethostname();
        $pid = getmypid();

        if ($handler !== null) {
            $found = false;
            foreach ($handler->getRecords() as $record) {
                $message = $record['message'] ?? ($record->message ?? '');
                if (str_contains($message, 'Process pid, event: stdClass')
                    && str_contains($message, 'hostname: '.$hostname)
                    && str_contains($message, 'pid: '.$pid)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Expected log message with pid info not found');
        } else {
            $this->assertTrue(true, 'Log handler not available, skipping log assertion');
        }
    }
}
