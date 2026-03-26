<?php

namespace HughCube\Laravel\Knight\Tests\Console\Commands;

use HughCube\Laravel\Knight\Console\Commands\WalEventDispatchCommand;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class WalEventDispatchCommandTest extends TestCase
{
    /**
     * Create command with proper input/output initialized.
     *
     * @param array $options
     *
     * @return WalEventDispatchCommand
     */
    protected function makeCommand(array $options = [])
    {
        $command = new WalEventDispatchCommand();
        $command->setLaravel($this->app);

        $input = new ArrayInput($options, $command->getDefinition());
        self::setProperty($command, 'input', $input);

        $bufferedOutput = new BufferedOutput();
        self::setProperty($command, 'output', new OutputStyle($input, $bufferedOutput));

        return $command;
    }

    // ==================== buildWal2jsonParamPlaceholders / buildWal2jsonParamBindings ====================

    public function testBuildWal2jsonParamPlaceholdersEmpty()
    {
        $command = $this->makeCommand();

        $result = self::callMethod($command, 'buildWal2jsonParamPlaceholders');
        $this->assertSame('', $result);
    }

    public function testBuildWal2jsonParamPlaceholdersWithParams()
    {
        $command = $this->makeCommand(['--wal2json-params' => ['filter-columns=content', 'add-tables=users']]);

        $result = self::callMethod($command, 'buildWal2jsonParamPlaceholders');
        $this->assertSame(', ?, ?, ?, ?', $result);
    }

    public function testBuildWal2jsonParamBindingsEmpty()
    {
        $command = $this->makeCommand();

        $result = self::callMethod($command, 'buildWal2jsonParamBindings');
        $this->assertSame([], $result);
    }

    public function testBuildWal2jsonParamBindingsWithParams()
    {
        $command = $this->makeCommand(['--wal2json-params' => ['filter-columns=content,body', 'add-tables=users']]);

        $result = self::callMethod($command, 'buildWal2jsonParamBindings');
        $this->assertSame(['filter-columns', 'content,body', 'add-tables', 'users'], $result);
    }

    public function testBuildWal2jsonParamBindingsWithKeyOnly()
    {
        $command = $this->makeCommand(['--wal2json-params' => ['include-lsn']]);

        $result = self::callMethod($command, 'buildWal2jsonParamBindings');
        $this->assertSame(['include-lsn', ''], $result);
    }

    // ==================== buildChangeSummary ====================

    public function testBuildChangeSummaryMixed()
    {
        $command = $this->makeCommand();

        $changes = [
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('insert', 'users', null, 1, 'id', [], [], 'stdClass'),
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('insert', 'users', null, 2, 'id', [], [], 'stdClass'),
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('update', 'users', null, 3, 'id', [], [], 'stdClass'),
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('delete', 'users', null, 4, 'id', [], [], 'stdClass'),
        ];

        $result = self::callMethod($command, 'buildChangeSummary', [$changes]);
        $this->assertSame('users: 2 inserts, 1 update, 1 delete', $result);
    }

    public function testBuildChangeSummarySingle()
    {
        $command = $this->makeCommand();

        $changes = [
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('insert', 'users', null, 1, 'id', [], [], 'stdClass'),
        ];

        $result = self::callMethod($command, 'buildChangeSummary', [$changes]);
        $this->assertSame('users: 1 insert', $result);
    }

    public function testBuildChangeSummaryMultipleTables()
    {
        $command = $this->makeCommand();

        $changes = [
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('insert', 'users', null, 1, 'id', [], [], 'stdClass'),
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('update', 'orders', null, 2, 'id', [], [], 'stdClass'),
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('update', 'orders', null, 3, 'id', [], [], 'stdClass'),
            new \HughCube\Laravel\Knight\Database\Wal\WalChangeRecord('delete', 'orders', null, 4, 'id', [], [], 'stdClass'),
        ];

        $result = self::callMethod($command, 'buildChangeSummary', [$changes]);
        $this->assertSame('users: 1 insert; orders: 2 updates, 1 delete', $result);
    }

    // ==================== getSlotName ====================

    public function testGetSlotNameDefaultUsesAppName()
    {
        $this->app['config']->set('app.name', 'MyTestApp');
        $this->app['config']->set('app.env', 'testing');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $this->assertSame('mytestapp_testing_wal_event', $result);
    }

    public function testGetSlotNameWithExplicitSlotOption()
    {
        $this->app['config']->set('app.name', 'MyApp');

        $command = $this->makeCommand(['--slot' => 'custom_slot']);

        $result = self::callMethod($command, 'getSlotName');
        $this->assertSame('custom_slot', $result);
    }

    public function testGetSlotNameWithSpecialCharacters()
    {
        $this->app['config']->set('app.name', 'My-Test.App!');
        $this->app['config']->set('app.env', 'testing');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $this->assertSame('my_test_app_testing_wal_event', $result);
    }

    public function testGetSlotNameWithEmptyAppName()
    {
        $this->app['config']->set('app.name', null);
        $this->app['config']->set('app.env', 'testing');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $this->assertSame('app_testing_wal_event', $result);
    }

    // ==================== resolveTable ====================

    public function testResolveTableWithPartitionMatch()
    {
        $command = $this->makeCommand();
        $partitionMap = [
            'orders_2024_01' => 'orders',
            'orders_2024_02' => 'orders',
            'logs_archive'   => 'logs',
        ];

        $result = self::callMethod($command, 'resolveTable', ['orders_2024_01', $partitionMap]);
        $this->assertSame('orders', $result);

        $result = self::callMethod($command, 'resolveTable', ['orders_2024_02', $partitionMap]);
        $this->assertSame('orders', $result);

        $result = self::callMethod($command, 'resolveTable', ['logs_archive', $partitionMap]);
        $this->assertSame('logs', $result);
    }

    public function testResolveTableWithoutPartitionMatch()
    {
        $command = $this->makeCommand();
        $partitionMap = [
            'orders_2024_01' => 'orders',
        ];

        $result = self::callMethod($command, 'resolveTable', ['users', $partitionMap]);
        $this->assertSame('users', $result);
    }

    public function testResolveTableWithEmptyPartitionMap()
    {
        $command = $this->makeCommand();

        $result = self::callMethod($command, 'resolveTable', ['any_table', []]);
        $this->assertSame('any_table', $result);
    }

    // ==================== getModelPaths ====================

    public function testGetModelPathsDefault()
    {
        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getModelPaths');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $keys = array_keys($result);
        $values = array_values($result);

        $this->assertStringContainsString('Models', $keys[0]);
        $this->assertSame('App\\Models\\', $values[0]);
    }

    public function testGetModelPathsWithCustomPath()
    {
        $command = $this->makeCommand(['--model-path' => ['app/Domain/Models:App\\Domain\\Models']]);

        $result = self::callMethod($command, 'getModelPaths');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $values = array_values($result);
        $this->assertSame('App\\Domain\\Models\\', $values[0]);
    }

    public function testGetModelPathsWithMultiplePaths()
    {
        $command = $this->makeCommand(['--model-path' => [
            'app/Models:App\\Models',
            'app/Domain:App\\Domain',
        ]]);

        $result = self::callMethod($command, 'getModelPaths');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testGetModelPathsWithPathWithoutNamespace()
    {
        $command = $this->makeCommand(['--model-path' => ['app/Models']]);

        $result = self::callMethod($command, 'getModelPaths');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $values = array_values($result);
        $this->assertSame('App\\Models\\', $values[0]);
    }

    // ==================== Command Signature ====================

    public function testCommandSignatureIsRegistered()
    {
        $commands = $this->app['Illuminate\Contracts\Console\Kernel']->all();

        $this->assertArrayHasKey('wal:event-dispatch', $commands);
    }

    public function testCommandHasExpectedOptions()
    {
        $command = $this->makeCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('connection'));
        $this->assertTrue($definition->hasOption('slot'));
        $this->assertTrue($definition->hasOption('interval'));
        $this->assertTrue($definition->hasOption('batch'));
        $this->assertTrue($definition->hasOption('mode'));
        $this->assertTrue($definition->hasOption('model-path'));
        $this->assertTrue($definition->hasOption('wal2json-params'));
    }

    public function testCommandOptionDefaults()
    {
        $command = $this->makeCommand();
        $definition = $command->getDefinition();

        $this->assertSame('1.0', $definition->getOption('interval')->getDefault());
        $this->assertSame('1000', $definition->getOption('batch')->getDefault());
        $this->assertSame('advance', $definition->getOption('mode')->getDefault());
        $this->assertNull($definition->getOption('connection')->getDefault());
        $this->assertNull($definition->getOption('slot')->getDefault());
    }

    // ==================== getMode ====================

    public function testGetModeDefaultIsAdvance()
    {
        $command = $this->makeCommand();

        $this->assertSame('advance', self::callMethod($command, 'getMode'));
    }

    public function testGetModeSupportsAutoAndPeek()
    {
        $auto = $this->makeCommand(['--mode' => 'auto']);
        $peek = $this->makeCommand(['--mode' => 'peek']);

        $this->assertSame('auto', self::callMethod($auto, 'getMode'));
        $this->assertSame('peek', self::callMethod($peek, 'getMode'));
    }

    public function testGetModeInvalidValueFallsBackToAdvance()
    {
        $command = $this->makeCommand(['--mode' => 'invalid']);

        $this->assertSame('advance', self::callMethod($command, 'getMode'));
    }

    // ==================== shouldRun / errorStreak ====================

    public function testInitialShouldRunIsTrue()
    {
        $command = $this->makeCommand();

        $this->assertTrue(self::getProperty($command, 'shouldRun'));
    }

    public function testInitialErrorStreakIsZero()
    {
        $command = $this->makeCommand();

        $this->assertSame(0, self::getProperty($command, 'errorStreak'));
    }

    // ==================== discoverWalHandlers ====================

    public function testDiscoverWalHandlersWithNonExistentPath()
    {
        $command = $this->makeCommand(['--model-path' => ['/nonexistent/path/models:App\\Nonexistent\\']]);

        $handlers = self::callMethod($command, 'discoverWalHandlers');

        $this->assertIsArray($handlers);
        $this->assertEmpty($handlers);
    }

    // ==================== PostgreSQL-dependent tests ====================

    public function testBuildPartitionMapOnNonPgsql()
    {
        $command = $this->makeCommand();

        $result = self::callMethod($command, 'buildPartitionMap');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testBuildPartitionMapOnPgsql()
    {
        $this->skipIfPgsqlNotConfigured();

        $command = $this->makeCommand(['--connection' => 'pgsql']);

        $result = self::callMethod($command, 'buildPartitionMap');

        $this->assertIsArray($result);
    }

    public function testEnsureSlotExistsOnPgsql()
    {
        $this->skipIfPgsqlNotConfigured();
        $this->skipIfWal2jsonNotAvailable();

        $command = $this->makeCommand(['--connection' => 'pgsql']);

        $slotName = 'knight_test_wal_'.time();

        try {
            self::callMethod($command, 'ensureSlotExists', [$slotName]);

            $connection = $this->app['db']->connection('pgsql');
            $exists = $connection->selectOne(
                'SELECT 1 FROM pg_replication_slots WHERE slot_name = ?',
                [$slotName]
            );
            $this->assertNotNull($exists);

            // Calling again should not throw (slot already exists)
            self::callMethod($command, 'ensureSlotExists', [$slotName]);
            $this->assertTrue(true);
        } finally {
            try {
                $this->app['db']->connection('pgsql')->statement(
                    'SELECT pg_drop_replication_slot(?)',
                    [$slotName]
                );
            } catch (\Throwable $e) {
                // ignore cleanup errors
            }
        }
    }

    public function testReconnectDatabaseOnSqlite()
    {
        $command = $this->makeCommand();

        self::callMethod($command, 'reconnectDatabase');
        $this->assertTrue(true);
    }

    public function testRegisterSignalHandlersDoesNotThrow()
    {
        $command = $this->makeCommand();

        self::callMethod($command, 'registerSignalHandlers');
        $this->assertTrue(true);
    }

    public function testGetConnection()
    {
        $command = $this->makeCommand();

        $connection = self::callMethod($command, 'getConnection');
        $this->assertInstanceOf(\Illuminate\Database\Connection::class, $connection);
    }

    public function testGetConnectionWithPgsql()
    {
        $this->skipIfPgsqlNotConfigured();

        $command = $this->makeCommand(['--connection' => 'pgsql']);

        $connection = self::callMethod($command, 'getConnection');
        $this->assertInstanceOf(\Illuminate\Database\Connection::class, $connection);
        $this->assertSame('pgsql', $connection->getDriverName());
    }

    // ==================== getSlotName strict tests ====================

    public function testGetSlotNameWithNumericAppName()
    {
        $this->app['config']->set('app.name', '123App456');
        $this->app['config']->set('app.env', 'testing');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $this->assertSame('123app456_testing_wal_event', $result);
    }

    public function testGetSlotNameWithChineseAppName()
    {
        $this->app['config']->set('app.name', '我的应用');
        $this->app['config']->set('app.env', 'testing');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $appEnvSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', '我的应用_testing'));
        $appEnvSlug = preg_replace('/_+/', '_', trim($appEnvSlug, '_'));
        $this->assertSame($appEnvSlug.'_wal_event', $result);
    }

    public function testGetSlotNameWithAllSpecialCharacters()
    {
        $this->app['config']->set('app.name', '@#$%^&*');
        $this->app['config']->set('app.env', 'testing');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $this->assertSame('testing_wal_event', $result);
    }

    // ==================== resolveTable strict tests ====================

    public function testResolveTableChainedPartitions()
    {
        $command = $this->makeCommand();

        $partitionMap = [
            'child_partition'  => 'parent_partition',
            'parent_partition' => 'grandparent',
        ];

        $result = self::callMethod($command, 'resolveTable', ['child_partition', $partitionMap]);
        $this->assertSame('parent_partition', $result);
    }

    public function testResolveTableIsCaseSensitive()
    {
        $command = $this->makeCommand();

        $partitionMap = [
            'Users' => 'users',
        ];

        $this->assertSame('users', self::callMethod($command, 'resolveTable', ['Users', $partitionMap]));
        $this->assertSame('users', self::callMethod($command, 'resolveTable', ['users', $partitionMap]));
    }

    // ==================== errorStreak / backoff tests ====================

    public function testErrorStreakCanBeModified()
    {
        $command = $this->makeCommand();

        self::setProperty($command, 'errorStreak', 5);
        $this->assertSame(5, self::getProperty($command, 'errorStreak'));

        self::setProperty($command, 'errorStreak', 0);
        $this->assertSame(0, self::getProperty($command, 'errorStreak'));
    }

    public function testShouldRunCanBeStopped()
    {
        $command = $this->makeCommand();

        $this->assertTrue(self::getProperty($command, 'shouldRun'));

        self::setProperty($command, 'shouldRun', false);
        $this->assertFalse(self::getProperty($command, 'shouldRun'));
    }

    public function testExponentialBackoffFormula()
    {
        $this->assertSame(1, intval(min(60, pow(2, 1 - 1))));
        $this->assertSame(2, intval(min(60, pow(2, 2 - 1))));
        $this->assertSame(4, intval(min(60, pow(2, 3 - 1))));
        $this->assertSame(8, intval(min(60, pow(2, 4 - 1))));
        $this->assertSame(16, intval(min(60, pow(2, 5 - 1))));
        $this->assertSame(32, intval(min(60, pow(2, 6 - 1))));
        $this->assertSame(60, intval(min(60, pow(2, 7 - 1))));
        $this->assertSame(60, intval(min(60, pow(2, 10 - 1))));
    }

    // ==================== getModelPaths strict tests ====================

    public function testGetModelPathsNamespaceTrimsTrailingBackslash()
    {
        $command = $this->makeCommand(['--model-path' => ['app/Models:App\\Models\\\\']]);

        $result = self::callMethod($command, 'getModelPaths');
        $values = array_values($result);

        $this->assertSame('App\\Models\\', $values[0]);
    }

    public function testGetModelPathsWithColonInPath()
    {
        $command = $this->makeCommand(['--model-path' => ['src/Models:My\\App\\Models']]);

        $result = self::callMethod($command, 'getModelPaths');
        $values = array_values($result);

        $this->assertSame('My\\App\\Models\\', $values[0]);
    }

    // ==================== queue options ====================

    public function testCommandHasQueueOptions()
    {
        $command = $this->makeCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('queue'));
        $this->assertTrue($definition->hasOption('queue-connection'));
    }

    public function testCommandQueueOptionDefaults()
    {
        $command = $this->makeCommand();
        $definition = $command->getDefinition();

        $this->assertNull($definition->getOption('queue')->getDefault());
        $this->assertSame('sync', $definition->getOption('queue-connection')->getDefault());
    }

    // ==================== command description ====================

    public function testCommandDescription()
    {
        $command = $this->makeCommand();

        $this->assertNotEmpty($command->getDescription());
    }

    /**
     * Skip if wal2json is not available on the PostgreSQL server.
     */
    protected function skipIfWal2jsonNotAvailable(): void
    {
        try {
            $connection = $this->app['db']->connection('pgsql');
            $slotName = 'knight_wal2json_check_'.time();

            $connection->statement(
                "SELECT pg_create_logical_replication_slot(?, 'wal2json')",
                [$slotName]
            );

            $connection->statement('SELECT pg_drop_replication_slot(?)', [$slotName]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('wal2json is not available: '.$e->getMessage());
        }
    }
}
