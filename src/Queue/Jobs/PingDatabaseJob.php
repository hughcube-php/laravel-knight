<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Exception;
use HughCube\Laravel\Knight\Queue\Job;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        $connection = DB::connection($this->p('connection'));

        $writeResultMessage = $this->pingResultMessage($connection, false);

        $readResultMessage = null;
        if (!empty($connection->getConfig('read'))) {
            $readResultMessage = $this->pingResultMessage($connection, false);
        }

        $this->info(sprintf(
            'connection: %s, write => ( %s ), read => ( %s ), 心跳成功',
            $connection->getName(),
            $writeResultMessage,
            ($readResultMessage ?? '-')
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
        $duration = Carbon::now()->diffInMilliseconds($now);

        return sprintf('result: %s, duration: %sms', $result, $duration);
    }
}
