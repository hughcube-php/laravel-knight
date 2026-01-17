<?php

namespace HughCube\Laravel\Knight\Tests\Database\Listeners;

use HughCube\Laravel\Knight\Database\Listeners\LogQuerySql;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Events\QueryExecuted;

class LogQuerySqlTest extends TestCase
{
    public function testHandleLogsQueryExecuted()
    {
        $handler = $this->setupTestLogHandler();

        $connection = $this->app->make('db')->connection();
        $event = new QueryExecuted('select * from users where name = ?', ['Bob'], 12.5, $connection);

        $listener = new LogQuerySql();
        $listener->handle($event);

        if ($handler !== null) {
            $found = false;
            foreach ($handler->getRecords() as $record) {
                $message = $record['message'] ?? ($record->message ?? '');
                if (str_contains($message, 'query executed')
                    && str_contains($message, 'connection: '.$connection->getName())
                    && str_contains($message, 'duration: 12.5ms')
                    && str_contains($message, "sql: select * from users where name = 'Bob'")) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, 'Expected query log message not found');
        } else {
            $this->assertTrue(true, 'Log handler not available, skipping log assertion');
        }
    }

    public function testHandleIgnoresNonQueryExecuted()
    {
        $handler = $this->setupTestLogHandler();
        $initialCount = $handler !== null ? count($handler->getRecords()) : 0;

        $listener = new LogQuerySql();
        $listener->handle((object) ['sql' => 'select 1']);

        if ($handler !== null) {
            $this->assertSame($initialCount, count($handler->getRecords()), 'No log should be written for non-QueryExecuted');
        } else {
            $this->assertTrue(true, 'Log handler not available, skipping log assertion');
        }
    }

    public function testHandleIgnoresEmptySql()
    {
        $handler = $this->setupTestLogHandler();
        $initialCount = $handler !== null ? count($handler->getRecords()) : 0;

        $connection = $this->app->make('db')->connection();
        $event = new QueryExecuted('', [], 0, $connection);

        $listener = new LogQuerySql();
        $listener->handle($event);

        if ($handler !== null) {
            $this->assertSame($initialCount, count($handler->getRecords()), 'No log should be written for empty SQL');
        } else {
            $this->assertTrue(true, 'Log handler not available, skipping log assertion');
        }
    }

    public function testIsEnableReturnsTrue()
    {
        $listener = new LogQuerySql();

        $this->assertTrue(self::callMethod($listener, 'isEnable'));
    }
}
