<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/2/22
 * Time: 11:18.
 */

namespace HughCube\Laravel\Knight\OPcache\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class ClearCliCacheCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected $signature = 'opcache:clear-cli-cache';

    /**
     * @inheritdoc
     */
    protected $description = 'Clear OPcache for CLI environment';

    /**
     * @param Schedule $schedule
     *
     * @throws Exception
     *
     * @return void
     */
    public function handle(Schedule $schedule)
    {
        if (!extension_loaded('Zend OPcache')) {
            $this->error('You do not have the Zend OPcache extension loaded, sample data is being shown instead.');

            return;
        }

        if (false === opcache_get_status()) {
            $this->warn('OPcache is not enabled.');

            return;
        }

        if (!opcache_reset()) {
            throw new Exception('Failed to reset OPcache.');
        }

        $this->info('The OPcache is reset successfully.');
    }
}
