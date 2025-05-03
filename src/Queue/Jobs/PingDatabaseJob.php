<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Exception;
use HughCube\Laravel\Knight\Queue\Job;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;

class PingDatabaseJob extends Job
{
    public function rules(): array
    {
        return [
            'connection' => ['nullable', 'string'],
        ];
    }

    /**
     * @throws Exception
     */
    protected function action(): void
    {
        $beginMemoryUsage = memory_get_usage();

        $connection = DB::connection($this->p('connection'));

        $writeResultMessage = $this->pingResultMessage($connection, false);

        $readResultMessage = null;
        if (!empty($connection->getConfig('read'))) {
            $readResultMessage = $this->pingResultMessage($connection, false);
        }

        $terminatedMemoryUsage = memory_get_usage();

        $this->info(sprintf(
            'memory: %s => %s, connection: %s, write: %s, read: %s',
            Number::fileSize($beginMemoryUsage),
            Number::fileSize($terminatedMemoryUsage),
            $connection->getName(),
            $writeResultMessage,
            $readResultMessage ?? '-'
        ));
    }

    protected function prepareSql(Connection $connection): string
    {
        if ('mysql' === $connection->getDriverName()) {
            return 'SELECT CONNECTION_ID()';
        }

        return 'SELECT 1';
    }

    protected function pingResultMessage(Connection $connection, $useReadPdo = true): string
    {
        $sql = $this->prepareSql($connection);

        $now = Carbon::now();
        $result = Collection::wrap((array) $connection->selectOne($sql, [], $useReadPdo))->first();
        $duration = $now->diffInMilliseconds(Carbon::now());

        return sprintf('conn#%s completed ping in %sms', $result, $duration);
    }
}
