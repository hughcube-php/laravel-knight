<?php

namespace HughCube\Laravel\Knight\Tests\Console\Commands;

use HughCube\Laravel\Knight\Console\Commands\DatabaseResetAutoIncrementStartId;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;

class DatabaseResetAutoIncrementStartIdTest extends TestCase
{
    private $originalDb;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDb = $this->app->make('db');
    }

    protected function tearDown(): void
    {
        DB::swap($this->originalDb);
        $this->app->instance('db', $this->originalDb);

        parent::tearDown();
    }

    private function swapDb($dbManager): void
    {
        $this->app->instance('db', $dbManager);
        DB::swap($dbManager);
    }

    private function makeDbManager($connection): object
    {
        return new class($connection) {
            public $lastConnection = null;
            private $connection;

            public function __construct($connection)
            {
                $this->connection = $connection;
            }

            public function connection($name = null)
            {
                $this->lastConnection = $name;

                return $this->connection;
            }
        };
    }

    private function makeConnection(array $rows): object
    {
        return new class($rows) {
            public array $selectCalls = [];
            public array $rows;
            public $pdo;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
                $this->pdo = new class {
                    public array $execCalls = [];

                    public function exec($sql)
                    {
                        $this->execCalls[] = $sql;
                    }
                };
            }

            public function getPdo()
            {
                return $this->pdo;
            }

            public function select($sql)
            {
                $this->selectCalls[] = $sql;

                if ($sql === 'show tables;') {
                    return $this->rows;
                }

                return [];
            }
        };
    }

    private function makeCommand(array $options, array $confirmResponses): DatabaseResetAutoIncrementStartId
    {
        return new class($options, $confirmResponses) extends DatabaseResetAutoIncrementStartId {
            public array $optionsValues;
            public array $confirmResponses;
            public array $confirmQuestions = [];

            public function __construct(array $options, array $confirmResponses)
            {
                parent::__construct();

                $this->optionsValues = $options;
                $this->confirmResponses = $confirmResponses;
            }

            public function option($key = null)
            {
                return $this->optionsValues[$key] ?? null;
            }

            public function confirm($question, $default = false)
            {
                $this->confirmQuestions[] = $question;

                if (empty($this->confirmResponses)) {
                    return false;
                }

                return array_shift($this->confirmResponses);
            }
        };
    }

    public function testHandleSkipsTablesWhenNotConfirmed(): void
    {
        $rows = [
            (object) ['Tables_in_test' => 'users'],
            (object) ['Tables_in_test' => 'orders'],
        ];
        $connection = $this->makeConnection($rows);
        $dbManager = $this->makeDbManager($connection);
        $this->swapDb($dbManager);

        $command = $this->makeCommand(
            ['connection' => null, 'database' => null, 'start' => 1000],
            [false, false]
        );

        $command->handle(new Schedule());

        $this->assertSame(['show tables;'], $connection->selectCalls);
        $this->assertSame(
            [
                'set "users" table auto increment start id to "1000"?',
                'set "orders" table auto increment start id to "1000"?',
            ],
            $command->confirmQuestions
        );
        $this->assertSame([], $connection->pdo->execCalls);
    }

    public function testHandleAppliesStartIdWhenConfirmed(): void
    {
        $rows = [
            (object) ['Tables_in_test' => 'users'],
            (object) ['Tables_in_test' => 'orders'],
        ];
        $connection = $this->makeConnection($rows);
        $dbManager = $this->makeDbManager($connection);
        $this->swapDb($dbManager);

        $command = $this->makeCommand(
            ['connection' => 'tenant', 'database' => 'knight', 'start' => 99],
            [false, true]
        );

        $command->handle(new Schedule());

        $this->assertSame('tenant', $dbManager->lastConnection);
        $this->assertSame(['use `knight`;'], $connection->pdo->execCalls);
        $this->assertSame(
            [
                'show tables;',
                'ALTER TABLE `orders` AUTO_INCREMENT = 99;',
            ],
            $connection->selectCalls
        );
    }
}
