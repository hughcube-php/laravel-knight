<?php

namespace HughCube\Laravel\Knight\Console\Commands {
    class WalEventDispatchCommandRuntimeState
    {
        public static bool $mockPcntl = false;
        public static bool $mockSleep = false;
        public static array $sleepCalls = [];
        public static array $asyncSignalCalls = [];
        public static array $signalHandlers = [];
        public static array $classExistsOverrides = [];

        public static function reset(): void
        {
            self::$mockPcntl = false;
            self::$mockSleep = false;
            self::$sleepCalls = [];
            self::$asyncSignalCalls = [];
            self::$signalHandlers = [];
            self::$classExistsOverrides = [];
        }
    }

    if (!function_exists(__NAMESPACE__.'\\extension_loaded')) {
        function extension_loaded($name)
        {
            if ($name === 'pcntl' && WalEventDispatchCommandRuntimeState::$mockPcntl) {
                return true;
            }

            return \extension_loaded($name);
        }
    }

    if (!function_exists(__NAMESPACE__.'\\pcntl_async_signals')) {
        function pcntl_async_signals($enable)
        {
            WalEventDispatchCommandRuntimeState::$asyncSignalCalls[] = $enable;

            return true;
        }
    }

    if (!function_exists(__NAMESPACE__.'\\pcntl_signal')) {
        function pcntl_signal($signal, $handler)
        {
            WalEventDispatchCommandRuntimeState::$signalHandlers[$signal] = $handler;

            return true;
        }
    }

    if (!function_exists(__NAMESPACE__.'\\usleep')) {
        function usleep($microseconds)
        {
            if (WalEventDispatchCommandRuntimeState::$mockSleep) {
                WalEventDispatchCommandRuntimeState::$sleepCalls[] = (int) $microseconds;

                return 0;
            }

            return \usleep($microseconds);
        }
    }

    if (!function_exists(__NAMESPACE__.'\\class_exists')) {
        function class_exists($class, $autoload = true)
        {
            if (array_key_exists($class, WalEventDispatchCommandRuntimeState::$classExistsOverrides)) {
                return WalEventDispatchCommandRuntimeState::$classExistsOverrides[$class];
            }

            return \class_exists($class, $autoload);
        }
    }
}

namespace HughCube\Laravel\Knight\Tests\Console\Commands {
    use HughCube\Laravel\Knight\Console\Commands\WalEventDispatchCommand;
    use HughCube\Laravel\Knight\Console\Commands\WalEventDispatchCommandRuntimeState;
    use HughCube\Laravel\Knight\Contracts\Database\HasWalHandler;
    use HughCube\Laravel\Knight\Jobs\WalChangesDispatchJob;
    use HughCube\Laravel\Knight\Tests\TestCase;
    use Illuminate\Console\OutputStyle;
    use Illuminate\Contracts\Debug\ExceptionHandler;
    use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
    use Illuminate\Support\Facades\Queue;
    use Symfony\Component\Console\Input\ArrayInput;
    use Symfony\Component\Console\Output\BufferedOutput;

    class WalEventDispatchCommandBehaviorTest extends TestCase
    {
        protected function setUp(): void
        {
            parent::setUp();
            WalEventDispatchCommandRuntimeState::reset();
        }

        protected function tearDown(): void
        {
            WalEventDispatchCommandRuntimeState::reset();
            parent::tearDown();
        }

        public function testHandleRunsSingleIterationAndStops(): void
        {
            config(['app.name' => 'TestApp', 'app.env' => 'testing']);

            $command = new TestableWalEventDispatchCommand();
            $command->handlers = [
                'users' => [
                    ['handler' => new DummyWalHandler(), 'keyName' => 'id'],
                    ['handler' => new DummyWalHandlerNoKey(), 'keyName' => 'id'],
                ],
            ];
            $command->partitionMap = ['users_2024' => 'users'];
            $command->pollReturn = false;
            $command->exceptionHandler = $this->createMock(ExceptionHandler::class);

            WalEventDispatchCommandRuntimeState::$mockSleep = true;

            $this->initializeCommand($command, [
                '--interval' => '0.25',
                '--batch'    => '5',
            ]);

            $command->handle();

            $this->assertSame('ensureSlotExists', $command->calls[0][0]);
            $this->assertSame('testapp_testing_wal_event', $command->calls[0][1]);
            $this->assertSame('pollChanges', $command->calls[1][0]);
            $this->assertFalse($command->reconnectCalled);
            $this->assertContains(250000, WalEventDispatchCommandRuntimeState::$sleepCalls);
        }

        public function testHandlePollErrorReportsAndReconnectsWithBackoff(): void
        {
            $exceptionHandler = $this->createMock(ExceptionHandler::class);
            $exceptionHandler->expects($this->once())->method('report');

            $command = new TestableWalEventDispatchCommand();
            $command->handlers = [];
            $command->pollThrowable = new \RuntimeException('poll failed');
            $command->exceptionHandler = $exceptionHandler;

            WalEventDispatchCommandRuntimeState::$mockSleep = true;

            $this->initializeCommand($command, [
                '--interval' => '0.1',
                '--batch'    => '10',
            ]);

            $command->handle();

            $this->assertTrue($command->reconnectCalled);
            $this->assertContains(1000000, WalEventDispatchCommandRuntimeState::$sleepCalls);
            $this->assertSame(1, self::getProperty($command, 'errorStreak'));

            $ensureSlotCalls = array_filter($command->calls, static function ($call) {
                return $call[0] === 'ensureSlotExists';
            });
            $this->assertCount(2, $ensureSlotCalls, 'ensureSlotExists should be called at startup and in error recovery');
        }

        public function testHandlePollErrorEnsureSlotExistsFailureDoesNotCrash(): void
        {
            $exceptionHandler = $this->createMock(ExceptionHandler::class);
            $exceptionHandler->expects($this->once())->method('report');

            $command = new TestableWalEnsureSlotFailsCommand();
            $command->handlers = [];
            $command->pollThrowable = new \RuntimeException('slot deleted');
            $command->exceptionHandler = $exceptionHandler;
            $command->ensureSlotException = new \RuntimeException('create slot failed');

            WalEventDispatchCommandRuntimeState::$mockSleep = true;

            $this->initializeCommand($command, [
                '--interval' => '0.1',
                '--batch'    => '10',
            ]);

            $command->handle();

            $this->assertTrue($command->reconnectCalled);
            $this->assertContains(1000000, WalEventDispatchCommandRuntimeState::$sleepCalls);
            $this->assertSame(1, self::getProperty($command, 'errorStreak'));
            $this->assertTrue($command->ensureSlotCalledInErrorPath);
        }

        public function testHandleExitsWhenConsecutiveErrorsReachMaxErrors(): void
        {
            $exceptionHandler = $this->createMock(ExceptionHandler::class);

            $command = new TestableWalMultiErrorCommand();
            $command->handlers = [];
            $command->pollThrowable = new \RuntimeException('persistent failure');
            $command->exceptionHandler = $exceptionHandler;

            WalEventDispatchCommandRuntimeState::$mockSleep = true;

            $this->initializeCommand($command, [
                '--interval'   => '0.1',
                '--batch'      => '10',
                '--max-errors' => '3',
            ]);

            $command->handle();

            $this->assertSame(3, self::getProperty($command, 'errorStreak'));

            $pollCalls = array_filter($command->calls, static function ($call) {
                return $call[0] === 'pollChanges';
            });
            $this->assertCount(3, $pollCalls);
        }

        public function testHandleContinuesWhenErrorsRecoverBeforeMaxErrors(): void
        {
            $exceptionHandler = $this->createMock(ExceptionHandler::class);

            $command = new TestableWalMultiErrorCommand();
            $command->handlers = [];
            $command->failCount = 2;
            $command->pollThrowable = new \RuntimeException('transient failure');
            $command->exceptionHandler = $exceptionHandler;

            WalEventDispatchCommandRuntimeState::$mockSleep = true;

            $this->initializeCommand($command, [
                '--interval'   => '0.1',
                '--batch'      => '10',
                '--max-errors' => '5',
            ]);

            $command->handle();

            $this->assertSame(0, self::getProperty($command, 'errorStreak'));

            $pollCalls = array_filter($command->calls, static function ($call) {
                return $call[0] === 'pollChanges';
            });
            $this->assertCount(3, $pollCalls);
        }

        public function testHandleUnlimitedErrorsWhenMaxErrorsIsZero(): void
        {
            $exceptionHandler = $this->createMock(ExceptionHandler::class);

            $command = new TestableWalMultiErrorCommand();
            $command->handlers = [];
            $command->failCount = 5;
            $command->pollThrowable = new \RuntimeException('failure');
            $command->exceptionHandler = $exceptionHandler;

            WalEventDispatchCommandRuntimeState::$mockSleep = true;

            $this->initializeCommand($command, [
                '--interval'   => '0.1',
                '--batch'      => '10',
                '--max-errors' => '0',
            ]);

            $command->handle();

            $this->assertSame(0, self::getProperty($command, 'errorStreak'));

            $pollCalls = array_filter($command->calls, static function ($call) {
                return $call[0] === 'pollChanges';
            });
            $this->assertCount(6, $pollCalls);
        }

        public function testDiscoverWalHandlersScansValidAndInvalidClasses(): void
        {
            $relativeDir = 'tests/.temp/wal_scan_'.md5((string) microtime(true));
            $absoluteDir = base_path($relativeDir);
            @mkdir($absoluteDir, 0777, true);

            file_put_contents($absoluteDir.'/NoClass.php', "<?php\nnamespace TestWal;\n// no class\n");
            file_put_contents($absoluteDir.'/PlainClass.php', "<?php\nnamespace TestWal;\nclass PlainClass {}\n");
            file_put_contents(
                $absoluteDir.'/AbstractWal.php',
                <<<'PHP'
<?php
namespace TestWal;
abstract class AbstractWal implements \HughCube\Laravel\Knight\Contracts\Database\HasWalHandler
{
    public function getTable() { return 'orders'; }
    public function onKnightModelChanged(): void {}
}
PHP
            );
            file_put_contents(
                $absoluteDir.'/ValidWal.php',
                <<<'PHP'
<?php
namespace TestWal;
class ValidWal implements \HughCube\Laravel\Knight\Contracts\Database\HasWalHandler
{
    public function getTable() { return 'orders'; }
    public function getKeyName() { return 'uuid'; }
    public function onKnightModelChanged(): void {}
}
PHP
            );
            file_put_contents(
                $absoluteDir.'/ValidWalDefaultKey.php',
                <<<'PHP'
<?php
namespace TestWal;
class ValidWalDefaultKey implements \HughCube\Laravel\Knight\Contracts\Database\HasWalHandler
{
    public function getTable() { return 'orders'; }
    public function onKnightModelChanged(): void {}
}
PHP
            );
            file_put_contents($absoluteDir.'/BrokenReflection.php', "<?php\nnamespace TestWal;\n// intentionally no class\n");
            file_put_contents($absoluteDir.'/README.txt', 'ignore');

            WalEventDispatchCommandRuntimeState::$classExistsOverrides['TestWal\\BrokenReflection'] = true;

            $loader = function ($class) use ($absoluteDir) {
                $prefix = 'TestWal\\';
                if (strpos($class, $prefix) !== 0) {
                    return;
                }

                $relative = substr($class, strlen($prefix));
                $path = $absoluteDir.'/'.str_replace('\\', '/', $relative).'.php';
                if (is_file($path)) {
                    require_once $path;
                }
            };
            spl_autoload_register($loader);

            try {
                $command = new WalEventDispatchCommand();
                $this->initializeCommand($command, [
                    '--model-path' => [$relativeDir.':TestWal'],
                ]);

                $handlers = self::callMethod($command, 'discoverWalHandlers');

                $this->assertArrayHasKey('orders', $handlers);
                $this->assertCount(2, $handlers['orders']);

                $keyNames = array_values(array_map(static function ($meta) {
                    return $meta['keyName'];
                }, $handlers['orders']));
                sort($keyNames);
                $this->assertSame(['id', 'uuid'], $keyNames);
            } finally {
                spl_autoload_unregister($loader);
                $this->deleteDirectory($absoluteDir);
            }
        }

        public function testBuildPartitionMapMapsReturnedRows(): void
        {
            $command = new class() extends WalEventDispatchCommand {
                public $connection;

                protected function getConnection()
                {
                    return $this->connection;
                }
            };
            $command->connection = new class() {
                public function select($query, array $bindings = [])
                {
                    return [
                        (object) ['child_table' => 'orders_2024_01', 'parent_table' => 'orders'],
                        (object) ['child_table' => 'orders_2024_02', 'parent_table' => 'orders'],
                    ];
                }
            };

            $partitionMap = self::callMethod($command, 'buildPartitionMap');

            $this->assertSame([
                'orders_2024_01' => 'orders',
                'orders_2024_02' => 'orders',
            ], $partitionMap);
        }

        public function testPollChangesSkipsInvalidRowsAndAdvancesSlot(): void
        {
            Queue::fake();

            $rows = [
                (object) ['lsn' => '0/1', 'data' => '{invalid-json'],
                (object) ['lsn' => '0/2', 'data' => json_encode(['change' => []])],
                (object) ['lsn' => '0/3', 'data' => json_encode(['change' => [
                    ['kind' => 'update', 'columnnames' => ['id'], 'columnvalues' => [1]],
                ]])],
                (object) ['lsn' => '0/4', 'data' => json_encode(['change' => [
                    ['kind' => 'update', 'table' => 'users', 'columnnames' => ['name'], 'columnvalues' => ['alice']],
                ]])],
                (object) ['lsn' => '0/5', 'data' => json_encode(['change' => [
                    ['kind' => 'update', 'table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [1]],
                ]])],
                (object) ['lsn' => '0/6', 'data' => json_encode(['change' => [
                    ['kind' => 'update', 'table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [1]],
                ]])],
            ];

            $mockPdo = new MockCursorPdo($rows);

            $connection = new class($rows, $mockPdo) {
                public array $rows;
                public array $statements = [];
                private $pdo;

                public function __construct(array $rows, $pdo)
                {
                    $this->rows = $rows;
                    $this->pdo = $pdo;
                }

                public function getPdo()
                {
                    return $this->pdo;
                }

                public function selectOne($query, array $bindings = [])
                {
                    if (false !== strpos($query, 'pg_current_wal_lsn')) {
                        return (object) ['lsn' => '0/0'];
                    }
                    return null;
                }

                public function select($query, array $bindings = [])
                {
                    return $this->rows;
                }

                public function statement($query, array $bindings = [])
                {
                    $this->statements[] = ['query' => $query, 'bindings' => $bindings];

                    return true;
                }
            };

            $command = new class() extends WalEventDispatchCommand {
                public $connection;

                protected function getConnection()
                {
                    return $this->connection;
                }
            };

            $this->initializeCommand($command, ['--mode' => 'advance', '--format-version' => '1']);
            $command->connection = $connection;

            $handlers = [
                'users' => [
                    ['handler' => new DummyWalHandler(), 'keyName' => 'id'],
                ],
            ];

            $result = self::callMethod($command, 'pollChanges', ['slot_test', $handlers, 1000, []]);

            $this->assertTrue($result);
            $this->assertCount(1, $connection->statements);
            $this->assertStringContainsString('pg_replication_slot_advance', $connection->statements[0]['query']);
            $this->assertSame(['slot_test', '0/6'], $connection->statements[0]['bindings']);

            // Two WAL entries for id=1 (no dedup), skip rows without table/id
            Queue::assertPushed(WalChangesDispatchJob::class, 2);
            Queue::assertPushed(WalChangesDispatchJob::class, function (WalChangesDispatchJob $job) {
                return $job->getRecord()->getId() == 1;
            });
        }

        public function testPollChangesInAutoModeUsesGetChangesAndDoesNotAdvanceSlot(): void
        {
            Queue::fake();

            $rows = [
                (object) ['lsn' => '0/A', 'data' => json_encode(['change' => [
                    ['kind' => 'update', 'table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [10]],
                ]])],
            ];

            $mockPdo = new MockCursorPdo($rows);

            $connection = new class($rows, $mockPdo) {
                public array $rows;
                public array $statements = [];
                private $pdo;

                public function __construct(array $rows, $pdo)
                {
                    $this->rows = $rows;
                    $this->pdo = $pdo;
                }

                public function getPdo()
                {
                    return $this->pdo;
                }

                public function selectOne($query, array $bindings = [])
                {
                    return null;
                }

                public function statement($query, array $bindings = [])
                {
                    $this->statements[] = ['query' => $query, 'bindings' => $bindings];

                    return true;
                }
            };

            $command = new class() extends WalEventDispatchCommand {
                public $connection;

                protected function getConnection()
                {
                    return $this->connection;
                }
            };

            $this->initializeCommand($command, ['--mode' => 'auto', '--format-version' => '1']);
            $command->connection = $connection;

            $handlers = [
                'users' => [
                    ['handler' => new DummyWalHandler(), 'keyName' => 'id'],
                ],
            ];

            $result = self::callMethod($command, 'pollChanges', ['slot_test_auto', $handlers, 1000, []]);

            $this->assertTrue($result);
            /** auto mode uses get_changes - verify via the SQL in PDO prepare */
            $this->assertNotEmpty($mockPdo->execCalls);
            $this->assertEmpty($connection->statements);

            Queue::assertPushed(WalChangesDispatchJob::class, 1);
            Queue::assertPushed(WalChangesDispatchJob::class, function (WalChangesDispatchJob $job) {
                return $job->getRecord()->getId() == 10;
            });
        }

        public function testPollChangesInPeekModeDoesNotAdvanceSlot(): void
        {
            Queue::fake();

            $rows = [
                (object) ['lsn' => '0/B', 'data' => json_encode(['change' => [
                    ['kind' => 'insert', 'table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [11]],
                ]])],
            ];

            $mockPdo = new MockCursorPdo($rows);

            $connection = new class($rows, $mockPdo) {
                public array $rows;
                public array $statements = [];
                private $pdo;

                public function __construct(array $rows, $pdo)
                {
                    $this->rows = $rows;
                    $this->pdo = $pdo;
                }

                public function getPdo()
                {
                    return $this->pdo;
                }

                public function selectOne($query, array $bindings = [])
                {
                    return null;
                }

                public function statement($query, array $bindings = [])
                {
                    $this->statements[] = ['query' => $query, 'bindings' => $bindings];

                    return true;
                }
            };

            $command = new class() extends WalEventDispatchCommand {
                public $connection;

                protected function getConnection()
                {
                    return $this->connection;
                }
            };

            $this->initializeCommand($command, ['--mode' => 'peek', '--format-version' => '1']);
            $command->connection = $connection;

            $handlers = [
                'users' => [
                    ['handler' => new DummyWalHandler(), 'keyName' => 'id'],
                ],
            ];

            $result = self::callMethod($command, 'pollChanges', ['slot_test_peek', $handlers, 1000, []]);

            $this->assertTrue($result);
            $this->assertEmpty($connection->statements);

            Queue::assertPushed(WalChangesDispatchJob::class, 1);
            Queue::assertPushed(WalChangesDispatchJob::class, function (WalChangesDispatchJob $job) {
                return $job->getRecord()->getId() == 11;
            });
        }

        public function testPollChangesAdvanceModeIgnoresSlotAdvanceFailure(): void
        {
            Queue::fake();

            $rows = [
                (object) ['lsn' => '0/C', 'data' => json_encode(['change' => [
                    ['kind' => 'insert', 'table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [12]],
                ]])],
            ];

            $mockPdo = new MockCursorPdo($rows);

            $connection = new class($rows, $mockPdo) {
                public array $rows;
                public int $statementCalls = 0;
                private $pdo;

                public function __construct(array $rows, $pdo)
                {
                    $this->rows = $rows;
                    $this->pdo = $pdo;
                }

                public function getPdo()
                {
                    return $this->pdo;
                }

                public function selectOne($query, array $bindings = [])
                {
                    if (false !== strpos($query, 'pg_current_wal_lsn')) {
                        return (object) ['lsn' => '0/0'];
                    }
                    return null;
                }

                public function statement($query, array $bindings = [])
                {
                    $this->statementCalls++;

                    throw new \RuntimeException('advance failed');
                }
            };

            $command = new class() extends WalEventDispatchCommand {
                public $connection;

                protected function getConnection()
                {
                    return $this->connection;
                }
            };

            $this->initializeCommand($command, ['--mode' => 'advance', '--format-version' => '1']);
            $command->connection = $connection;

            $handlers = [
                'users' => [
                    ['handler' => new DummyWalHandler(), 'keyName' => 'id'],
                ],
            ];

            $result = self::callMethod($command, 'pollChanges', ['slot_test_advance', $handlers, 1000, []]);

            $this->assertTrue($result);
            $this->assertSame(1, $connection->statementCalls);

            Queue::assertPushed(WalChangesDispatchJob::class, 1);
            Queue::assertPushed(WalChangesDispatchJob::class, function (WalChangesDispatchJob $job) {
                return $job->getRecord()->getId() == 12;
            });
        }

        public function testHandleStopsImmediatelyOnErrorClass(): void
        {
            $exceptionHandler = $this->createMock(ExceptionHandler::class);
            $exceptionHandler->expects($this->once())->method('report');

            $command = new TestableWalEventDispatchCommand();
            $command->handlers = [];
            /** TypeError extends \Error，应立即停止不重试 */
            $command->pollThrowable = new \TypeError('type error in code');
            $command->exceptionHandler = $exceptionHandler;

            WalEventDispatchCommandRuntimeState::$mockSleep = true;

            $this->initializeCommand($command, [
                '--interval'   => '0.1',
                '--batch'      => '10',
                '--max-errors' => '10',
            ]);

            $command->handle();

            /** Error 不应触发指数退避重试，errorStreak 应为 0（Error 路径不增加） */
            $this->assertSame(0, self::getProperty($command, 'errorStreak'));

            /** 只应有 1 次 pollChanges 调用（立即退出，不重试） */
            $pollCalls = array_filter($command->calls, static function ($call) {
                return $call[0] === 'pollChanges';
            });
            $this->assertCount(1, $pollCalls);

            /** 不应触发 reconnect */
            $this->assertFalse($command->reconnectCalled);

            /** 不应有退避 sleep */
            $this->assertEmpty(WalEventDispatchCommandRuntimeState::$sleepCalls);
        }

        public function testRegisterSignalHandlersUsesPcntlWhenAvailable(): void
        {
            if (!defined('SIGTERM')) {
                define('SIGTERM', 15);
            }
            if (!defined('SIGINT')) {
                define('SIGINT', 2);
            }

            WalEventDispatchCommandRuntimeState::$mockPcntl = true;

            $command = new WalEventDispatchCommand();
            $this->initializeCommand($command);

            self::setProperty($command, 'shouldRun', true);
            self::callMethod($command, 'registerSignalHandlers');

            $this->assertNotEmpty(WalEventDispatchCommandRuntimeState::$asyncSignalCalls);
            $this->assertArrayHasKey(SIGTERM, WalEventDispatchCommandRuntimeState::$signalHandlers);
            $this->assertArrayHasKey(SIGINT, WalEventDispatchCommandRuntimeState::$signalHandlers);

            $handler = WalEventDispatchCommandRuntimeState::$signalHandlers[SIGTERM];
            $this->assertIsCallable($handler);
            $handler();

            $this->assertFalse(self::getProperty($command, 'shouldRun'));

            self::setProperty($command, 'shouldRun', true);
            $interruptHandler = WalEventDispatchCommandRuntimeState::$signalHandlers[SIGINT];
            $this->assertIsCallable($interruptHandler);
            $interruptHandler();

            $this->assertFalse(self::getProperty($command, 'shouldRun'));
        }

        public function testReconnectDatabaseHandlesReconnectFailure(): void
        {
            $command = new class() extends WalEventDispatchCommand {
                protected function getConnection()
                {
                    return new class() {
                        public function reconnect(): void
                        {
                            throw new \RuntimeException('reconnect failed');
                        }
                    };
                }
            };

            $this->initializeCommand($command);

            self::callMethod($command, 'reconnectDatabase');
            $this->assertTrue(true);
        }

        private function initializeCommand(WalEventDispatchCommand $command, array $options = []): WalEventDispatchCommand
        {
            $command->setLaravel($this->app);

            $input = new ArrayInput($options, $command->getDefinition());
            self::setProperty($command, 'input', $input);

            $bufferedOutput = new BufferedOutput();
            self::setProperty($command, 'output', new OutputStyle($input, $bufferedOutput));

            return $command;
        }

        private function deleteDirectory(string $dir): void
        {
            if (!is_dir($dir)) {
                return;
            }

            $items = scandir($dir);
            if (!is_array($items)) {
                @rmdir($dir);

                return;
            }

            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $path = $dir.DIRECTORY_SEPARATOR.$item;
                if (is_dir($path)) {
                    $this->deleteDirectory($path);
                } else {
                    @unlink($path);
                }
            }

            @rmdir($dir);
        }
    }

    class TestableWalEventDispatchCommand extends WalEventDispatchCommand
    {
        public array $handlers = [];
        public array $partitionMap = [];
        public bool $pollReturn = false;
        public ?\Throwable $pollThrowable = null;
        public array $calls = [];
        public $connection = null;
        public ?EventsDispatcher $eventsDispatcher = null;
        public ?ExceptionHandler $exceptionHandler = null;
        public bool $reconnectCalled = false;

        protected function ensureSlotExists(string $slot): void
        {
            $this->calls[] = ['ensureSlotExists', $slot];
        }

        protected function discoverWalHandlers(): array
        {
            return $this->handlers;
        }

        protected function buildPartitionMap(): array
        {
            return $this->partitionMap;
        }

        protected function pollChanges(string $slot, array $handlers, int $batch, array $partitionMap): bool
        {
            $this->calls[] = ['pollChanges', $slot, $batch, count($handlers), count($partitionMap)];

            if ($this->pollThrowable !== null) {
                $this->shouldRun = false;

                throw $this->pollThrowable;
            }

            $this->shouldRun = false;

            return $this->pollReturn;
        }

        protected function getExceptionHandler(): ExceptionHandler
        {
            if ($this->exceptionHandler !== null) {
                return $this->exceptionHandler;
            }

            return parent::getExceptionHandler();
        }

        protected function getEventsDispatcher(): EventsDispatcher
        {
            if ($this->eventsDispatcher !== null) {
                return $this->eventsDispatcher;
            }

            return parent::getEventsDispatcher();
        }

        protected function getConnection()
        {
            if ($this->connection !== null) {
                return $this->connection;
            }

            return parent::getConnection();
        }

        protected function reconnectDatabase(): void
        {
            $this->reconnectCalled = true;
            $this->shouldRun = false;
        }
    }

    class TestableWalMultiErrorCommand extends TestableWalEventDispatchCommand
    {
        /** @var int 0 means always fail */
        public int $failCount = 0;
        private int $pollAttempt = 0;

        protected function pollChanges(string $slot, array $handlers, int $batch, array $partitionMap): bool
        {
            $this->pollAttempt++;
            $this->calls[] = ['pollChanges', $slot, $batch, count($handlers), count($partitionMap)];

            if ($this->failCount > 0 && $this->pollAttempt > $this->failCount) {
                $this->shouldRun = false;

                return false;
            }

            if ($this->pollThrowable !== null) {
                throw $this->pollThrowable;
            }

            $this->shouldRun = false;

            return false;
        }

        protected function reconnectDatabase(): void
        {
            $this->reconnectCalled = true;
        }
    }

    class TestableWalEnsureSlotFailsCommand extends TestableWalEventDispatchCommand
    {
        public ?\Throwable $ensureSlotException = null;
        public bool $ensureSlotCalledInErrorPath = false;
        private bool $firstCall = true;

        protected function ensureSlotExists(string $slot): void
        {
            if ($this->firstCall) {
                $this->firstCall = false;
                parent::ensureSlotExists($slot);

                return;
            }

            $this->ensureSlotCalledInErrorPath = true;

            if ($this->ensureSlotException !== null) {
                throw $this->ensureSlotException;
            }
        }
    }

    class DummyWalHandler extends \HughCube\Laravel\Knight\Database\Eloquent\Model implements HasWalHandler
    {
        use \HughCube\Laravel\Knight\Database\Eloquent\Traits\HasWalHandlerTrait;

        protected $table = 'users';

        protected $fillable = ['id', 'name'];

        public function onKnightModelChanged(): void
        {
        }
    }

    class DummyWalHandlerNoKey extends \HughCube\Laravel\Knight\Database\Eloquent\Model implements HasWalHandler
    {
        use \HughCube\Laravel\Knight\Database\Eloquent\Traits\HasWalHandlerTrait;

        protected $table = 'users';

        protected $fillable = ['id', 'name'];

        public function onKnightModelChanged(): void
        {
        }
    }

    /**
     * Mock PDO for cursor-based pollChanges tests.
     * Simulates DECLARE CURSOR / FETCH / CLOSE behavior.
     */
    class MockCursorPdo
    {
        /** @var array */
        public $rows;
        /** @var int */
        private $fetchIndex = 0;
        /** @var int */
        private $fetchSize = 1000;
        /** @var array */
        public $execCalls = [];

        public function __construct(array $rows)
        {
            $this->rows = $rows;
        }

        public function exec($sql)
        {
            $this->execCalls[] = $sql;
            return 0;
        }

        public function prepare($sql)
        {
            return new MockCursorStatement($this);
        }

        public function query($sql)
        {
            if (preg_match('/FETCH\s+(\d+)/i', $sql, $m)) {
                $this->fetchSize = (int) $m[1];
            }
            $chunk = array_slice($this->rows, $this->fetchIndex, $this->fetchSize);
            $this->fetchIndex += count($chunk);
            return new MockCursorResult($chunk);
        }
    }

    class MockCursorStatement
    {
        private $pdo;
        public function __construct($pdo) { $this->pdo = $pdo; }
        public function execute($bindings = []) { return true; }
    }

    class MockCursorResult
    {
        private $data;
        public function __construct(array $data) { $this->data = $data; }
        public function fetchAll($mode = \PDO::FETCH_OBJ) { return $this->data; }
    }
}
