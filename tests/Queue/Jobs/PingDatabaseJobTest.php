<?php

namespace HughCube\Laravel\Knight\Tests\Queue\Jobs;

use HughCube\Laravel\Knight\Queue\Jobs\PingDatabaseJob;
use HughCube\Laravel\Knight\Tests\TestCase;
use Illuminate\Database\Connection;

class PingDatabaseJobTest extends TestCase
{
    public function testRun()
    {
        $this->assertJob(PingDatabaseJob::new());
    }

    public function testPrepareSqlUsesDriverName()
    {
        $job = new PingDatabaseJob();

        $mysql = new FakeConnection('mysql', (object) ['id' => 1]);
        $pgsql = new FakeConnection('pgsql', (object) ['id' => 1]);
        $sqlite = new FakeConnection('sqlite', (object) ['id' => 1]);

        $this->assertSame('SELECT CONNECTION_ID()', $this->callMethod($job, 'prepareSql', [$mysql]));
        $this->assertSame('SELECT pg_backend_pid()', $this->callMethod($job, 'prepareSql', [$pgsql]));
        $this->assertSame('SELECT 1', $this->callMethod($job, 'prepareSql', [$sqlite]));
    }

    public function testPingResultMessageFormats()
    {
        $job = new PingDatabaseJob();
        $connection = new FakeConnection('mysql', (object) ['pid' => 99]);

        $message = $this->callMethod($job, 'pingResultMessage', [$connection, true]);

        $this->assertStringContainsString('conn#99#', $message);
        $this->assertStringContainsString('ms', $message);
    }
}

class FakeConnection extends Connection
{
    private string $fakeDriver;
    private $fakeResult;
    private array $fakeConfig;
    private string $fakeName;

    public function __construct(string $driver, $result, array $config = [], string $name = 'fake')
    {
        $this->fakeDriver = $driver;
        $this->fakeResult = $result;
        $this->fakeConfig = $config;
        $this->fakeName = $name;
    }

    public function getDriverName()
    {
        return $this->fakeDriver;
    }

    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        return $this->fakeResult;
    }

    public function getConfig($option = null)
    {
        return $option ? ($this->fakeConfig[$option] ?? null) : $this->fakeConfig;
    }

    public function getName()
    {
        return $this->fakeName;
    }
}
