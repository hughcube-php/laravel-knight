<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Exception;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Support\Carbon;
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

        $now = Carbon::now();
        $connection->select('select 1', [], false);
        $writePdoDuration = Carbon::now()->diffInMilliseconds($now);

        $readPdoDuration = null;
        if (!empty($connection->getConfig('read'))) {
            $now = Carbon::now();
            $connection->select('select 1');
            $readPdoDuration = Carbon::now()->diffInMilliseconds($now);
        }

        $this->info(sprintf(
            'connection: %s, write: %s, read: %s, 心跳成功',
            $connection->getName(),
            sprintf('%sms', $writePdoDuration),
            is_null($readPdoDuration) ? '-' : sprintf('%sms', $readPdoDuration)
        ));
    }
}
