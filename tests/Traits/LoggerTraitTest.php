<?php

namespace HughCube\Laravel\Knight\Tests\Traits;

use HughCube\Laravel\Knight\Tests\TestCase;
use HughCube\Laravel\Knight\Traits\Logger;
use Psr\Log\LogLevel;

class LoggerTraitTest extends TestCase
{
    public function testLoggerConvenienceMethods()
    {
        $logger = new TestLogger();

        $logger->emergency('emergency', ['a' => 1]);
        $logger->alert('alert', ['b' => 2]);
        $logger->critical('critical', ['c' => 3]);
        $logger->error('error', ['d' => 4]);
        $logger->warning('warning', ['e' => 5]);
        $logger->notice('notice', ['f' => 6]);
        $logger->info('info', ['g' => 7]);
        $logger->debug('debug', ['h' => 8]);

        $this->assertSame([
            [LogLevel::EMERGENCY, 'emergency', ['a' => 1]],
            [LogLevel::ALERT, 'alert', ['b' => 2]],
            [LogLevel::CRITICAL, 'critical', ['c' => 3]],
            [LogLevel::ERROR, 'error', ['d' => 4]],
            [LogLevel::WARNING, 'warning', ['e' => 5]],
            [LogLevel::NOTICE, 'notice', ['f' => 6]],
            [LogLevel::INFO, 'info', ['g' => 7]],
            [LogLevel::DEBUG, 'debug', ['h' => 8]],
        ], $logger->records);
    }
}

class TestLogger
{
    use Logger;

    public array $records = [];

    public function log($level, string $message, array $context = []): void
    {
        $this->records[] = [$level, $message, $context];
    }
}
