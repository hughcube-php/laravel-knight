<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/28
 * Time: 11:34.
 */

namespace HughCube\Laravel\Knight\Database\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

class LogQuerySql
{
    public function handle($event): void
    {
        if (!$this->isEnable()) {
            return;
        }

        if (!$event instanceof QueryExecuted || empty($event->sql)) {
            return;
        }

        Log::debug(
            'query executed'
            .sprintf(', connection: %s, duration: %sms', $event->connectionName, $event->time)
            .sprintf(', sql: %s', vsprintf(str_replace('?', "'%s'", $event->sql), $event->bindings))
        );
    }

    protected function isEnable(): bool
    {
        return true;
    }
}
