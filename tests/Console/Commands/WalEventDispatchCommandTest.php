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

    // ==================== extractPrimaryKey ====================

    public function testExtractPrimaryKeyForInsert()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'insert',
            'table' => 'users',
            'columnnames' => ['id', 'name', 'email'],
            'columnvalues' => [42, 'John', 'john@example.com'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame(42, $result);
    }

    public function testExtractPrimaryKeyForUpdate()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'update',
            'table' => 'users',
            'columnnames' => ['id', 'name', 'email'],
            'columnvalues' => [7, 'Jane', 'jane@example.com'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame(7, $result);
    }

    public function testExtractPrimaryKeyForDelete()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'delete',
            'table' => 'users',
            'oldkeys' => [
                'keynames' => ['id'],
                'keyvalues' => [15],
            ],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame(15, $result);
    }

    public function testExtractPrimaryKeyForDeleteWithStringKey()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'delete',
            'table' => 'items',
            'oldkeys' => [
                'keynames' => ['uuid'],
                'keyvalues' => ['abc-def-123'],
            ],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'uuid']);
        $this->assertSame('abc-def-123', $result);
    }

    public function testExtractPrimaryKeyForDeleteWithCompositeKey()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'delete',
            'table' => 'order_items',
            'oldkeys' => [
                'keynames' => ['order_id', 'item_id'],
                'keyvalues' => [10, 20],
            ],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'item_id']);
        $this->assertSame(20, $result);
    }

    public function testExtractPrimaryKeyReturnsNullWhenKeyNotFound()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'insert',
            'table' => 'users',
            'columnnames' => ['name', 'email'],
            'columnvalues' => ['John', 'john@example.com'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertNull($result);
    }

    public function testExtractPrimaryKeyForDeleteReturnsNullWhenKeyNotInOldkeys()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'delete',
            'table' => 'users',
            'oldkeys' => [
                'keynames' => ['other_key'],
                'keyvalues' => [1],
            ],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertNull($result);
    }

    public function testExtractPrimaryKeyForDeleteWithEmptyOldkeys()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'delete',
            'table' => 'users',
            'oldkeys' => [
                'keynames' => [],
                'keyvalues' => [],
            ],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertNull($result);
    }

    public function testExtractPrimaryKeyForDeleteWithMissingOldkeys()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'delete',
            'table' => 'users',
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertNull($result);
    }

    public function testExtractPrimaryKeyWithNoKind()
    {
        $command = $this->makeCommand();

        $change = [
            'table' => 'users',
            'columnnames' => ['id', 'name'],
            'columnvalues' => [5, 'test'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame(5, $result);
    }

    public function testExtractPrimaryKeyWithEmptyChange()
    {
        $command = $this->makeCommand();

        $result = self::callMethod($command, 'extractPrimaryKey', [[], 'id']);
        $this->assertNull($result);
    }

    public function testExtractPrimaryKeyWithStringIdInsert()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'insert',
            'table' => 'items',
            'columnnames' => ['uuid', 'name'],
            'columnvalues' => ['550e8400-e29b-41d4-a716-446655440000', 'Widget'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'uuid']);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result);
    }

    public function testExtractPrimaryKeyWithZeroId()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'insert',
            'table' => 'items',
            'columnnames' => ['id', 'name'],
            'columnvalues' => [0, 'zero'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame(0, $result);
    }

    public function testExtractPrimaryKeyWithNegativeId()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'insert',
            'table' => 'items',
            'columnnames' => ['id', 'name'],
            'columnvalues' => [-1, 'negative'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame(-1, $result);
    }

    public function testExtractPrimaryKeyWithLargeIntegerId()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'insert',
            'table' => 'items',
            'columnnames' => ['id', 'name'],
            'columnvalues' => [9999999999, 'large'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame(9999999999, $result);
    }

    public function testExtractPrimaryKeyWithEmptyStringId()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'insert',
            'table' => 'items',
            'columnnames' => ['id', 'name'],
            'columnvalues' => ['', 'empty_id'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame('', $result);
    }

    public function testExtractPrimaryKeyDeleteWithMultipleKeysFirstKey()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'delete',
            'table' => 'pivot',
            'oldkeys' => [
                'keynames' => ['user_id', 'role_id'],
                'keyvalues' => [100, 200],
            ],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'user_id']);
        $this->assertSame(100, $result);
    }

    public function testExtractPrimaryKeyDeleteWithUuidKey()
    {
        $command = $this->makeCommand();

        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $change = [
            'kind' => 'delete',
            'table' => 'documents',
            'oldkeys' => [
                'keynames' => ['uuid'],
                'keyvalues' => [$uuid],
            ],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'uuid']);
        $this->assertSame($uuid, $result);
    }

    public function testExtractPrimaryKeyWithMismatchedColumnArrayLengths()
    {
        $command = $this->makeCommand();

        // columnnames has more entries than columnvalues
        $change = [
            'kind' => 'insert',
            'table' => 'items',
            'columnnames' => ['id', 'name', 'extra'],
            'columnvalues' => [1, 'test'],
        ];

        // 'id' is at index 0, columnvalues[0] exists
        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        $this->assertSame(1, $result);

        // 'extra' is at index 2, columnvalues[2] does NOT exist
        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'extra']);
        $this->assertNull($result);
    }

    public function testExtractPrimaryKeyWithNullKeyValue()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'insert',
            'table' => 'items',
            'columnnames' => ['id', 'name'],
            'columnvalues' => [null, 'test'],
        ];

        // null is a valid value (isset returns false for null)
        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'id']);
        // isset($columnValues[0]) is false when value is null, so returns null
        $this->assertNull($result);
    }

    public function testExtractPrimaryKeyForUpdateWithStringPk()
    {
        $command = $this->makeCommand();

        $change = [
            'kind' => 'update',
            'table' => 'slugs',
            'columnnames' => ['slug', 'title'],
            'columnvalues' => ['my-article', 'My Article'],
        ];

        $result = self::callMethod($command, 'extractPrimaryKey', [$change, 'slug']);
        $this->assertSame('my-article', $result);
    }

    // ==================== getSlotName ====================

    public function testGetSlotNameDefaultUsesAppName()
    {
        $this->app['config']->set('app.name', 'MyTestApp');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $this->assertSame('mytestapp_wal_event', $result);
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

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $this->assertSame('my_test_app__wal_event', $result);
    }

    public function testGetSlotNameWithEmptyAppName()
    {
        $this->app['config']->set('app.name', null);

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        // config('app.name', 'app') returns null when key exists but value is null
        $this->assertSame('_wal_event', $result);
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
        $this->assertTrue($definition->hasOption('advance'));
        $this->assertTrue($definition->hasOption('model-path'));
    }

    public function testCommandOptionDefaults()
    {
        $command = $this->makeCommand();
        $definition = $command->getDefinition();

        $this->assertSame('1.0', $definition->getOption('interval')->getDefault());
        $this->assertSame('1000', $definition->getOption('batch')->getDefault());
        $this->assertNull($definition->getOption('connection')->getDefault());
        $this->assertNull($definition->getOption('slot')->getDefault());
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

        $slotName = 'knight_test_wal_' . time();

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

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        $this->assertSame('123app456_wal_event', $result);
    }

    public function testGetSlotNameWithChineseAppName()
    {
        $this->app['config']->set('app.name', '我的应用');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        // Chinese chars are multi-byte in UTF-8 (3 bytes each), preg_replace replaces per byte
        // '我的应用' = 4 chars × 3 bytes = 12 underscores, then + '_wal_event'
        $this->assertSame('_____________wal_event', $result);
    }

    public function testGetSlotNameWithAllSpecialCharacters()
    {
        $this->app['config']->set('app.name', '@#$%^&*');

        $command = $this->makeCommand();

        $result = self::callMethod($command, 'getSlotName');

        // 7 special chars → 7 underscores, then + '_wal_event'
        $this->assertSame('________wal_event', $result);
    }

    // ==================== resolveTable strict tests ====================

    public function testResolveTableChainedPartitions()
    {
        $command = $this->makeCommand();

        // Only one level of partition mapping is performed
        $partitionMap = [
            'child_partition' => 'parent_partition',
            'parent_partition' => 'grandparent',
        ];

        // Should resolve to parent_partition, NOT grandparent
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
        // Verify the formula: min(60, pow(2, errorStreak - 1))
        $this->assertSame(1, intval(min(60, pow(2, 1 - 1))));   // streak 1 -> 1s
        $this->assertSame(2, intval(min(60, pow(2, 2 - 1))));   // streak 2 -> 2s
        $this->assertSame(4, intval(min(60, pow(2, 3 - 1))));   // streak 3 -> 4s
        $this->assertSame(8, intval(min(60, pow(2, 4 - 1))));   // streak 4 -> 8s
        $this->assertSame(16, intval(min(60, pow(2, 5 - 1))));  // streak 5 -> 16s
        $this->assertSame(32, intval(min(60, pow(2, 6 - 1))));  // streak 6 -> 32s
        $this->assertSame(60, intval(min(60, pow(2, 7 - 1))));  // streak 7 -> 60s (capped)
        $this->assertSame(60, intval(min(60, pow(2, 10 - 1)))); // streak 10 -> 60s (capped)
    }

    // ==================== getModelPaths strict tests ====================

    public function testGetModelPathsNamespaceTrimsTrailingBackslash()
    {
        $command = $this->makeCommand(['--model-path' => ['app/Models:App\\Models\\\\']]);

        $result = self::callMethod($command, 'getModelPaths');
        $values = array_values($result);

        // Should have single trailing backslash
        $this->assertSame('App\\Models\\', $values[0]);
    }

    public function testGetModelPathsWithColonInPath()
    {
        // path:namespace format with namespace containing backslashes
        $command = $this->makeCommand(['--model-path' => ['src/Models:My\\App\\Models']]);

        $result = self::callMethod($command, 'getModelPaths');
        $values = array_values($result);

        $this->assertSame('My\\App\\Models\\', $values[0]);
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
            $slotName = 'knight_wal2json_check_' . time();

            $connection->statement(
                "SELECT pg_create_logical_replication_slot(?, 'wal2json')",
                [$slotName]
            );

            $connection->statement('SELECT pg_drop_replication_slot(?)', [$slotName]);
        } catch (\Throwable $e) {
            $this->markTestSkipped('wal2json is not available: ' . $e->getMessage());
        }
    }
}
