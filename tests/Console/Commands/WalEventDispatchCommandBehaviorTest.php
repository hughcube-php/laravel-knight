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

    if (!function_exists(__NAMESPACE__ . '\\extension_loaded')) {
        function extension_loaded($name)
        {
            if ($name === 'pcntl' && WalEventDispatchCommandRuntimeState::$mockPcntl) {
                return true;
            }

            return \extension_loaded($name);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\pcntl_async_signals')) {
        function pcntl_async_signals($enable)
        {
            WalEventDispatchCommandRuntimeState::$asyncSignalCalls[] = $enable;
            return true;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\pcntl_signal')) {
        function pcntl_signal($signal, $handler)
        {
            WalEventDispatchCommandRuntimeState::$signalHandlers[$signal] = $handler;
            return true;
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\usleep')) {
        function usleep($microseconds)
        {
            if (WalEventDispatchCommandRuntimeState::$mockSleep) {
                WalEventDispatchCommandRuntimeState::$sleepCalls[] = (int) $microseconds;
                return 0;
            }

            return \usleep($microseconds);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\\class_exists')) {
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
use HughCube\Laravel\Knight\Events\WalChangesDetected;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
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
        config(['app.name' => 'TestApp']);

        $command = new TestableWalEventDispatchCommand();
        $command->handlers = [
            'users' => [
                ['handler' => new DummyWalHandler('users', 'id'), 'keyName' => 'id'],
                ['handler' => new DummyWalHandlerNoKey('users'), 'keyName' => 'id'],
            ],
        ];
        $command->partitionMap = ['users_2024' => 'users'];
        $command->pollReturn = false;
        $command->exceptionHandler = $this->createMock(ExceptionHandler::class);

        WalEventDispatchCommandRuntimeState::$mockSleep = true;

        $this->initializeCommand($command, [
            '--interval' => '0.25',
            '--batch' => '5',
        ]);

        $command->handle();

        $this->assertSame('ensureSlotExists', $command->calls[0][0]);
        $this->assertSame('testapp_wal_event', $command->calls[0][1]);
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
            '--batch' => '10',
        ]);

        $command->handle();

        $this->assertTrue($command->reconnectCalled);
        $this->assertContains(1000000, WalEventDispatchCommandRuntimeState::$sleepCalls);
        $this->assertSame(1, self::getProperty($command, 'errorStreak'));
    }

    public function testDiscoverWalHandlersScansValidAndInvalidClasses(): void
    {
        $relativeDir = 'tests/.temp/wal_scan_' . md5((string) microtime(true));
        $absoluteDir = base_path($relativeDir);
        @mkdir($absoluteDir, 0777, true);

        file_put_contents($absoluteDir . '/NoClass.php', "<?php\nnamespace TestWal;\n// no class\n");
        file_put_contents($absoluteDir . '/PlainClass.php', "<?php\nnamespace TestWal;\nclass PlainClass {}\n");
        file_put_contents($absoluteDir . '/AbstractWal.php', <<<'PHP'
<?php
namespace TestWal;
abstract class AbstractWal implements \HughCube\Laravel\Knight\Contracts\Database\HasWalHandler
{
    public function getTable() { return 'orders'; }
    public function onKnightModelChanged(): void {}
}
PHP
        );
        file_put_contents($absoluteDir . '/ValidWal.php', <<<'PHP'
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
        file_put_contents($absoluteDir . '/ValidWalDefaultKey.php', <<<'PHP'
<?php
namespace TestWal;
class ValidWalDefaultKey implements \HughCube\Laravel\Knight\Contracts\Database\HasWalHandler
{
    public function getTable() { return 'orders'; }
    public function onKnightModelChanged(): void {}
}
PHP
        );
        file_put_contents($absoluteDir . '/BrokenReflection.php', "<?php\nnamespace TestWal;\n// intentionally no class\n");
        file_put_contents($absoluteDir . '/README.txt', 'ignore');

        WalEventDispatchCommandRuntimeState::$classExistsOverrides['TestWal\\BrokenReflection'] = true;

        $loader = function ($class) use ($absoluteDir) {
            $prefix = 'TestWal\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $path = $absoluteDir . '/' . str_replace('\\', '/', $relative) . '.php';
            if (is_file($path)) {
                require_once $path;
            }
        };
        spl_autoload_register($loader);

        try {
            $command = new WalEventDispatchCommand();
            $this->initializeCommand($command, [
                '--model-path' => [$relativeDir . ':TestWal'],
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
        $command = new class extends WalEventDispatchCommand {
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
        $dispatcher = $this->createMock(EventsDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($event) {
                return $event instanceof WalChangesDetected
                    && $event->handler instanceof DummyWalHandler
                    && $event->ids === [1];
            }));

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

        $connection = new class($rows) {
            public array $rows;
            public array $statements = [];

            public function __construct(array $rows)
            {
                $this->rows = $rows;
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

        $command = new class extends WalEventDispatchCommand {
            public $connection;
            public ?EventsDispatcher $dispatcher = null;

            protected function getConnection()
            {
                return $this->connection;
            }

            protected function getEventsDispatcher(): EventsDispatcher
            {
                if ($this->dispatcher !== null) {
                    return $this->dispatcher;
                }

                return parent::getEventsDispatcher();
            }
        };

        $this->initializeCommand($command, ['--mode' => 'advance']);
        $command->connection = $connection;
        $command->dispatcher = $dispatcher;

        $handlers = [
            'users' => [
                ['handler' => new DummyWalHandler('users', 'id'), 'keyName' => 'id'],
            ],
        ];

        $result = self::callMethod($command, 'pollChanges', ['slot_test', $handlers, 1000, []]);

        $this->assertTrue($result);
        $this->assertCount(1, $connection->statements);
        $this->assertStringContainsString('pg_replication_slot_advance', $connection->statements[0]['query']);
        $this->assertSame(['slot_test', '0/6'], $connection->statements[0]['bindings']);
    }

    public function testPollChangesInAutoModeUsesGetChangesAndDoesNotAdvanceSlot(): void
    {
        $dispatcher = $this->createMock(EventsDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($event) {
                return $event instanceof WalChangesDetected
                    && $event->handler instanceof DummyWalHandler
                    && $event->ids === [10];
            }));

        $rows = [
            (object) ['lsn' => '0/A', 'data' => json_encode(['change' => [
                ['kind' => 'update', 'table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [10]],
            ]])],
        ];

        $connection = new class($rows) {
            public array $rows;
            public array $selects = [];
            public array $statements = [];

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function select($query, array $bindings = [])
            {
                $this->selects[] = ['query' => $query, 'bindings' => $bindings];
                return $this->rows;
            }

            public function statement($query, array $bindings = [])
            {
                $this->statements[] = ['query' => $query, 'bindings' => $bindings];
                return true;
            }
        };

        $command = new class extends WalEventDispatchCommand {
            public $connection;
            public ?EventsDispatcher $dispatcher = null;

            protected function getConnection()
            {
                return $this->connection;
            }

            protected function getEventsDispatcher(): EventsDispatcher
            {
                if ($this->dispatcher !== null) {
                    return $this->dispatcher;
                }

                return parent::getEventsDispatcher();
            }
        };

        $this->initializeCommand($command, ['--mode' => 'auto']);
        $command->connection = $connection;
        $command->dispatcher = $dispatcher;

        $handlers = [
            'users' => [
                ['handler' => new DummyWalHandler('users', 'id'), 'keyName' => 'id'],
            ],
        ];

        $result = self::callMethod($command, 'pollChanges', ['slot_test_auto', $handlers, 1000, []]);

        $this->assertTrue($result);
        $this->assertCount(1, $connection->selects);
        $this->assertStringContainsString('pg_logical_slot_get_changes', $connection->selects[0]['query']);
        $this->assertEmpty($connection->statements);
    }

    public function testPollChangesInPeekModeDoesNotAdvanceSlot(): void
    {
        $dispatcher = $this->createMock(EventsDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($event) {
                return $event instanceof WalChangesDetected
                    && $event->handler instanceof DummyWalHandler
                    && $event->ids === [11];
            }));

        $rows = [
            (object) ['lsn' => '0/B', 'data' => json_encode(['change' => [
                ['kind' => 'insert', 'table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [11]],
            ]])],
        ];

        $connection = new class($rows) {
            public array $rows;
            public array $selects = [];
            public array $statements = [];

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function select($query, array $bindings = [])
            {
                $this->selects[] = ['query' => $query, 'bindings' => $bindings];
                return $this->rows;
            }

            public function statement($query, array $bindings = [])
            {
                $this->statements[] = ['query' => $query, 'bindings' => $bindings];
                return true;
            }
        };

        $command = new class extends WalEventDispatchCommand {
            public $connection;
            public ?EventsDispatcher $dispatcher = null;

            protected function getConnection()
            {
                return $this->connection;
            }

            protected function getEventsDispatcher(): EventsDispatcher
            {
                if ($this->dispatcher !== null) {
                    return $this->dispatcher;
                }

                return parent::getEventsDispatcher();
            }
        };

        $this->initializeCommand($command, ['--mode' => 'peek']);
        $command->connection = $connection;
        $command->dispatcher = $dispatcher;

        $handlers = [
            'users' => [
                ['handler' => new DummyWalHandler('users', 'id'), 'keyName' => 'id'],
            ],
        ];

        $result = self::callMethod($command, 'pollChanges', ['slot_test_peek', $handlers, 1000, []]);

        $this->assertTrue($result);
        $this->assertCount(1, $connection->selects);
        $this->assertStringContainsString('pg_logical_slot_peek_changes', $connection->selects[0]['query']);
        $this->assertEmpty($connection->statements);
    }

    public function testPollChangesAdvanceModeIgnoresSlotAdvanceFailure(): void
    {
        $dispatcher = $this->createMock(EventsDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(static function ($event) {
                return $event instanceof WalChangesDetected
                    && $event->handler instanceof DummyWalHandler
                    && $event->ids === [12];
            }));

        $rows = [
            (object) ['lsn' => '0/C', 'data' => json_encode(['change' => [
                ['kind' => 'insert', 'table' => 'users', 'columnnames' => ['id'], 'columnvalues' => [12]],
            ]])],
        ];

        $connection = new class($rows) {
            public array $rows;
            public int $statementCalls = 0;

            public function __construct(array $rows)
            {
                $this->rows = $rows;
            }

            public function select($query, array $bindings = [])
            {
                return $this->rows;
            }

            public function statement($query, array $bindings = [])
            {
                $this->statementCalls++;
                throw new \RuntimeException('advance failed');
            }
        };

        $command = new class extends WalEventDispatchCommand {
            public $connection;
            public ?EventsDispatcher $dispatcher = null;

            protected function getConnection()
            {
                return $this->connection;
            }

            protected function getEventsDispatcher(): EventsDispatcher
            {
                if ($this->dispatcher !== null) {
                    return $this->dispatcher;
                }

                return parent::getEventsDispatcher();
            }
        };

        $this->initializeCommand($command, ['--mode' => 'advance']);
        $command->connection = $connection;
        $command->dispatcher = $dispatcher;

        $handlers = [
            'users' => [
                ['handler' => new DummyWalHandler('users', 'id'), 'keyName' => 'id'],
            ],
        ];

        $result = self::callMethod($command, 'pollChanges', ['slot_test_advance', $handlers, 1000, []]);

        $this->assertTrue($result);
        $this->assertSame(1, $connection->statementCalls);
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
        $command = new class extends WalEventDispatchCommand {
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

            $path = $dir . DIRECTORY_SEPARATOR . $item;
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

class DummyWalHandler implements HasWalHandler
{
    private string $table;
    private string $keyName;

    public function __construct(string $table, string $keyName)
    {
        $this->table = $table;
        $this->keyName = $keyName;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getKeyName(): string
    {
        return $this->keyName;
    }

    public function onKnightModelChanged(): void
    {
    }
}

class DummyWalHandlerNoKey implements HasWalHandler
{
    private string $table;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function onKnightModelChanged(): void
    {
    }
}
}
