<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2025/5/12
 * Time: 23:04.
 */

namespace HughCube\Laravel\Knight\Console\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class DatabaseResetAutoIncrementStartId extends \HughCube\Laravel\Knight\Console\Command
{
    /**
     * @inheritdoc
     */
    protected $signature = 'database:reset-auto-increment-start-id
                    {--connection= : Database connection name }
                    {--database= : Database name }
                    {--start=10000000 : The starting value for auto-increment }
    ';

    /**
     * @inheritdoc
     */
    protected $description = 'Reset the auto-increment start ID for tables in the specified database.';

    /**
     * @throws Throwable
     */
    public function handle(Schedule $schedule): void
    {
        $connection = DB::connection($this->option('connection') ?: null);

        if (!empty($database = $this->option('database') ?: null)) {
            $connection->getPdo()->exec(sprintf('use `%s`;', $database));
        }

        $rows = $connection->select('show tables;');
        foreach ($rows as $row) {
            $start = $this->option('start');
            $table = Collection::wrap((array) $row)->first();

            if (true !== $this->confirm(sprintf('set "%s" table auto increment start id to "%s"?', $table, $start))) {
                continue;
            }

            $connection->select(sprintf('ALTER TABLE `%s` AUTO_INCREMENT = %s;', $table, $start));
        }
    }
}
