<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/28
 * Time: 11:34.
 */

namespace HughCube\Laravel\Knight\Listeners;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class LogPid
{
    public function handle($event = null): void
    {
        Log::info(sprintf(
            'Process pid, event: %s, hostname: %s, pid: %s',
            get_debug_type($event),
            gethostname(),
            getmypid()
        ));
    }
}
