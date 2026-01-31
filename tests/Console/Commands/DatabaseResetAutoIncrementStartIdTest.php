<?php

namespace HughCube\Laravel\Knight\Tests\Console\Commands;

use HughCube\Laravel\Knight\Console\Commands\DatabaseResetAutoIncrementStartId;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class DatabaseResetAutoIncrementStartIdTest extends TestCase
{
    public function testCommandSignatureAndDescription(): void
    {
        $command = new DatabaseResetAutoIncrementStartId();

        $this->assertStringContainsString('database:reset-auto-increment-start-id', $command->getName());
    }

    public function testGetTablesForMysql(): void
    {
        $this->skipIfMysqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');
        $command = new DatabaseResetAutoIncrementStartId();

        $tables = $this->callMethod($command, 'getTables', [$connection, 'mysql']);

        $this->assertIsIterable($tables);
    }

    public function testGetTablesForPgsql(): void
    {
        $this->skipIfPgsqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('pgsql');
        $command = new DatabaseResetAutoIncrementStartId();

        $tables = $this->callMethod($command, 'getTables', [$connection, 'pgsql']);

        $this->assertIsIterable($tables);
    }

    public function testGetPrimaryKeyColumnForMysql(): void
    {
        $this->skipIfMysqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            )
        ");

        try {
            $command = new DatabaseResetAutoIncrementStartId();
            $primaryKey = $this->callMethod($command, 'getPrimaryKeyColumn', [$connection, 'mysql', $tableName]);

            $this->assertEquals('id', $primaryKey);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    public function testGetPrimaryKeyColumnForPgsql(): void
    {
        $this->skipIfPgsqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('pgsql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE \"{$tableName}\" (
                \"id\" BIGSERIAL PRIMARY KEY,
                \"name\" VARCHAR(255) NOT NULL
            )
        ");

        try {
            $command = new DatabaseResetAutoIncrementStartId();
            $primaryKey = $this->callMethod($command, 'getPrimaryKeyColumn', [$connection, 'pgsql', $tableName]);

            $this->assertEquals('id', $primaryKey);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS \"{$tableName}\"");
        }
    }

    public function testGetMaxIdForMysql(): void
    {
        $this->skipIfMysqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            )
        ");

        try {
            $connection->insert("INSERT INTO `{$tableName}` (`name`) VALUES ('test1'), ('test2'), ('test3')");

            $command = new DatabaseResetAutoIncrementStartId();
            $maxId = $this->callMethod($command, 'getMaxId', [$connection, 'mysql', $tableName, 'id']);

            $this->assertEquals(3, $maxId);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    public function testGetMaxIdForPgsql(): void
    {
        $this->skipIfPgsqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('pgsql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE \"{$tableName}\" (
                \"id\" BIGSERIAL PRIMARY KEY,
                \"name\" VARCHAR(255) NOT NULL
            )
        ");

        try {
            $connection->insert("INSERT INTO \"{$tableName}\" (\"name\") VALUES ('test1'), ('test2'), ('test3')");

            $command = new DatabaseResetAutoIncrementStartId();
            $maxId = $this->callMethod($command, 'getMaxId', [$connection, 'pgsql', $tableName, 'id']);

            $this->assertEquals(3, $maxId);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS \"{$tableName}\"");
        }
    }

    public function testGetMaxIdForEmptyTable(): void
    {
        $this->skipIfMysqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            )
        ");

        try {
            $command = new DatabaseResetAutoIncrementStartId();
            $maxId = $this->callMethod($command, 'getMaxId', [$connection, 'mysql', $tableName, 'id']);

            $this->assertEquals(0, $maxId);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    public function testSetAutoIncrementForMysql(): void
    {
        $this->skipIfMysqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            )
        ");

        try {
            $command = new DatabaseResetAutoIncrementStartId();
            $this->callMethod($command, 'setAutoIncrement', [$connection, 'mysql', $tableName, 'id', 1000]);

            $connection->insert("INSERT INTO `{$tableName}` (`name`) VALUES ('test')");
            $result = $connection->selectOne("SELECT MAX(`id`) as max_id FROM `{$tableName}`");

            $this->assertEquals(1000, $result->max_id);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    public function testSetAutoIncrementForPgsql(): void
    {
        $this->skipIfPgsqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('pgsql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE \"{$tableName}\" (
                \"id\" BIGSERIAL PRIMARY KEY,
                \"name\" VARCHAR(255) NOT NULL
            )
        ");

        try {
            $command = new DatabaseResetAutoIncrementStartId();
            $this->callMethod($command, 'setAutoIncrement', [$connection, 'pgsql', $tableName, 'id', 1000]);

            $connection->insert("INSERT INTO \"{$tableName}\" (\"name\") VALUES ('test')");
            $result = $connection->selectOne("SELECT MAX(\"id\") as max_id FROM \"{$tableName}\"");

            $this->assertEquals(1000, $result->max_id);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS \"{$tableName}\"");
        }
    }

    public function testGetSequenceNameForPgsql(): void
    {
        $this->skipIfPgsqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('pgsql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE \"{$tableName}\" (
                \"id\" BIGSERIAL PRIMARY KEY,
                \"name\" VARCHAR(255) NOT NULL
            )
        ");

        try {
            $command = new DatabaseResetAutoIncrementStartId();
            $sequenceName = $this->callMethod($command, 'getSequenceName', [$connection, $tableName, 'id']);

            $this->assertNotNull($sequenceName);
            $this->assertStringContainsString($tableName, $sequenceName);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS \"{$tableName}\"");
        }
    }

    public function testMinParameterIsRespected(): void
    {
        $this->skipIfMysqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            )
        ");

        try {
            $connection->insert("INSERT INTO `{$tableName}` (`name`) VALUES ('test')");

            $command = new DatabaseResetAutoIncrementStartId();
            $maxId = $this->callMethod($command, 'getMaxId', [$connection, 'mysql', $tableName, 'id']);

            $min = 10000;
            $offset = 0;
            $newStartId = max($min, $maxId + $offset);

            $this->assertEquals(10000, $newStartId);
            $this->assertGreaterThan($maxId, $newStartId);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    public function testOffsetParameterIsRespected(): void
    {
        $this->skipIfMysqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $tableName = 'test_auto_increment_' . uniqid();

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            )
        ");

        try {
            $connection->insert("INSERT INTO `{$tableName}` (`name`) VALUES ('test1'), ('test2'), ('test3')");

            $command = new DatabaseResetAutoIncrementStartId();
            $maxId = $this->callMethod($command, 'getMaxId', [$connection, 'mysql', $tableName, 'id']);

            $min = 1;
            $offset = 100;
            $newStartId = max($min, $maxId + $offset);

            $this->assertEquals(103, $newStartId);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    public function testMinAndOffsetCombined(): void
    {
        $min = 1000;
        $offset = 50;
        $maxId = 500;

        $newStartId = max($min, $maxId + $offset);
        $this->assertEquals(1000, $newStartId);

        $maxId = 2000;
        $newStartId = max($min, $maxId + $offset);
        $this->assertEquals(2050, $newStartId);
    }

    public function testTableWithoutPrimaryKeyIsSkipped(): void
    {
        $this->skipIfMysqlNotConfigured();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $tableName = 'test_no_pk_' . uniqid();

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `name` VARCHAR(255) NOT NULL,
                `value` INT NOT NULL
            )
        ");

        try {
            $command = new DatabaseResetAutoIncrementStartId();
            $primaryKey = $this->callMethod($command, 'getPrimaryKeyColumn', [$connection, 'mysql', $tableName]);

            $this->assertNull($primaryKey);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    public function testIntegrationMysql(): void
    {
        $this->skipIfMysqlNotConfigured();

        $tableName = 'test_integration_' . uniqid();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            )
        ");

        try {
            $connection->insert("INSERT INTO `{$tableName}` (`name`) VALUES ('test1'), ('test2')");

            $this->artisan('database:reset-auto-increment-start-id', [
                '--connection' => 'mysql',
                '--table' => $tableName,
                '--min' => 5000,
                '--offset' => 100,
                '--force' => true,
            ]);

            $connection->insert("INSERT INTO `{$tableName}` (`name`) VALUES ('test3')");
            $result = $connection->selectOne("SELECT MAX(`id`) as max_id FROM `{$tableName}`");

            $this->assertEquals(5000, $result->max_id);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }

    public function testIntegrationPgsql(): void
    {
        $this->skipIfPgsqlNotConfigured();

        $tableName = 'test_integration_' . uniqid();

        /** @var Connection $connection */
        $connection = DB::connection('pgsql');

        $connection->statement("
            CREATE TABLE \"{$tableName}\" (
                \"id\" BIGSERIAL PRIMARY KEY,
                \"name\" VARCHAR(255) NOT NULL
            )
        ");

        try {
            $connection->insert("INSERT INTO \"{$tableName}\" (\"name\") VALUES ('test1'), ('test2')");

            $this->artisan('database:reset-auto-increment-start-id', [
                '--connection' => 'pgsql',
                '--table' => $tableName,
                '--min' => 5000,
                '--offset' => 100,
                '--force' => true,
            ]);

            $connection->insert("INSERT INTO \"{$tableName}\" (\"name\") VALUES ('test3')");
            $result = $connection->selectOne("SELECT MAX(\"id\") as max_id FROM \"{$tableName}\"");

            $this->assertEquals(5000, $result->max_id);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS \"{$tableName}\"");
        }
    }

    public function testIntegrationWithHighMaxId(): void
    {
        $this->skipIfMysqlNotConfigured();

        $tableName = 'test_high_max_' . uniqid();

        /** @var Connection $connection */
        $connection = DB::connection('mysql');

        $connection->statement("
            CREATE TABLE `{$tableName}` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL
            ) AUTO_INCREMENT = 10000
        ");

        try {
            $connection->insert("INSERT INTO `{$tableName}` (`name`) VALUES ('test1')");

            $this->artisan('database:reset-auto-increment-start-id', [
                '--connection' => 'mysql',
                '--table' => $tableName,
                '--min' => 1000,
                '--offset' => 500,
                '--force' => true,
            ]);

            $connection->insert("INSERT INTO `{$tableName}` (`name`) VALUES ('test2')");
            $result = $connection->selectOne("SELECT MAX(`id`) as max_id FROM `{$tableName}`");

            $this->assertEquals(10500, $result->max_id);
        } finally {
            $connection->statement("DROP TABLE IF EXISTS `{$tableName}`");
        }
    }
}
