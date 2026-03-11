<?php

namespace HughCube\Laravel\Knight\Console\Commands;

use HughCube\Laravel\Knight\Console\Command;

class WalDropSlotCommand extends Command
{
    protected $signature = 'wal:drop-slot
        {slot : Replication slot name}
        {--connection= : Database connection name}';

    protected $description = 'Drop a PostgreSQL logical replication slot';

    public function handle(): void
    {
        $slot = $this->argument('slot');
        $connectionName = $this->option('connection');

        /** @var \Illuminate\Database\Connection $connection */
        $connection = app('db')->connection($connectionName ?: null);

        $exists = $connection->selectOne(
            'SELECT 1 FROM pg_replication_slots WHERE slot_name = ?',
            [$slot]
        );

        if (null === $exists) {
            $this->error(sprintf('Slot [%s] does not exist', $slot));

            return;
        }

        $connection->statement('SELECT pg_drop_replication_slot(?)', [$slot]);
        $this->info(sprintf('Slot [%s] dropped successfully', $slot));
    }
}
