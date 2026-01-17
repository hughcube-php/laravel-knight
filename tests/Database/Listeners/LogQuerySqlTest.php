<?php

namespace HughCube\Laravel\Knight\Tests\Database\Listeners;

use HughCube\Laravel\Knight\Database\Listeners\LogQuerySql;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class LogQuerySqlTest extends TestCase
{
    public function testHandleLogsQueryExecuted()
    {
        Log::spy();

        $connection = $this->app->make('db')->connection();
        $event = new QueryExecuted('select * from users where name = ?', ['Bob'], 12.5, $connection);

        $listener = new LogQuerySql();
        $listener->handle($event);

        Log::shouldHaveReceived('debug')
            ->once()
            ->withArgs(function ($message) use ($connection) {
                return is_string($message)
                    && str_contains($message, 'query executed')
                    && str_contains($message, 'connection: '.$connection->getName())
                    && str_contains($message, 'duration: 12.5ms')
                    && str_contains($message, "sql: select * from users where name = 'Bob'");
            });
    }

    public function testHandleIgnoresNonQueryExecuted()
    {
        Log::spy();

        $listener = new LogQuerySql();
        $listener->handle((object) ['sql' => 'select 1']);

        Log::shouldNotHaveReceived('debug');
    }

    public function testHandleIgnoresEmptySql()
    {
        Log::spy();

        $connection = $this->app->make('db')->connection();
        $event = new QueryExecuted('', [], 0, $connection);

        $listener = new LogQuerySql();
        $listener->handle($event);

        Log::shouldNotHaveReceived('debug');
    }

    public function testIsEnableReturnsTrue()
    {
        $listener = new LogQuerySql();

        $this->assertTrue(self::callMethod($listener, 'isEnable'));
    }
}
