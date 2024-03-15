<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/28
 * Time: 11:34
 */

namespace HughCube\Laravel\Knight\Listeners;

use Illuminate\Support\Facades\Log;

class LogPid
{
    public function handle($event = null): void
    {
        Log::info(sprintf('event: %s, pid: %s', get_debug_type($event), getmypid()));
    }
}
