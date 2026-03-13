<?php

namespace HughCube\Laravel\Knight\Console\Commands;

use HughCube\Laravel\Knight\Console\Command;
use HughCube\Laravel\Knight\Contracts\Database\HasWalHandler;
use HughCube\Laravel\Knight\Events\WalChangesDetected;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Throwable;

class WalEventDispatchCommand extends Command
{
    protected $signature = 'wal:event-dispatch
        {--connection= : Database connection name}
        {--slot= : Replication slot name, default: APP_NAME_APP_ENV_wal_event}
        {--interval=1.0 : Poll interval in seconds, supports decimals}
        {--batch=1000 : Max number of changes per poll}
        {--memory=128 : Memory limit in MB, process stops when exceeded}
        {--max-errors=30 : Max consecutive errors before process exits, 0 means unlimited}
        {--mode=advance : Consume mode: auto(get_changes, read=consume), advance(peek+advance after dispatch, default), peek(read-only for debugging)}
        {--model-path=* : Model scanning paths, can be specified multiple times (default: app/Models)}';

    protected $description = 'Monitor PostgreSQL WAL changes and dispatch corresponding events';

    /**
     * @var bool
     */
    protected $shouldRun = true;

    /**
     * @var int Consecutive error count for exponential backoff.
     */
    protected $errorStreak = 0;

    public function handle(): void
    {
        $slot = $this->getSlotName();
        $interval = max(0.01, floatval($this->option('interval')));
        $batch = max(1, intval($this->option('batch')));

        $this->ensureSlotExists($slot);
        $this->registerSignalHandlers();

        $handlers = $this->discoverWalHandlers();
        $partitionMap = $this->buildPartitionMap();

        $handlerCount = 0;
        foreach ($handlers as $metas) {
            $handlerCount += count($metas);
        }

        $memoryLimit = max(1, intval($this->option('memory')));
        $maxErrors = max(0, intval($this->option('max-errors')));

        $this->info(sprintf(
            'WAL event dispatch started, slot: %s, mode: %s, interval: %.2fs, batch: %d, memory: %dMB, max-errors: %s, tables: %d, handlers: %d, partitions: %d',
            $slot,
            $this->getMode(),
            $interval,
            $batch,
            $memoryLimit,
            $maxErrors > 0 ? $maxErrors : 'unlimited',
            count($handlers),
            $handlerCount,
            count($partitionMap)
        ));
        foreach ($handlers as $table => $metas) {
            foreach ($metas as $meta) {
                $this->info(sprintf('  -> %s (table: %s, key: %s)', get_class($meta['handler']), $table, $meta['keyName']));
            }
        }

        while ($this->shouldRun) {
            $hasChanges = false;

            try {
                $hasChanges = $this->pollChanges($slot, $handlers, $batch, $partitionMap);

                $this->errorStreak = 0;
                $this->flushTelescopeEntries();
            } catch (Throwable $e) {
                $this->errorStreak++;

                /** #3 指数退避: 1s → 2s → 4s → ... → 60s cap */
                $backoff = min(60, pow(2, $this->errorStreak - 1));
                $this->error(sprintf("Poll error (streak %d, backoff %ds): %s\n%s", $this->errorStreak, $backoff, $e->getMessage(), $e->getTraceAsString()));
                $this->getExceptionHandler()->report($e);
                $this->reconnectDatabase();

                try {
                    $this->ensureSlotExists($slot);
                } catch (Throwable $re) {
                    $this->error(sprintf('Slot re-create failed: %s', $re->getMessage()));
                }

                if ($maxErrors > 0 && $this->errorStreak >= $maxErrors) {
                    $this->error(sprintf('Consecutive errors reached limit (%d), stopping...', $maxErrors));
                    break;
                }

                usleep(intval($backoff * 1000000));
                continue;
            }

            if ($this->isMemoryExceeded($memoryLimit)) {
                $this->info(sprintf(
                    'Memory limit of %dMB exceeded (current: %dMB), stopping...',
                    $memoryLimit,
                    intval(memory_get_usage(true) / 1024 / 1024)
                ));
                break;
            }

            if (!$hasChanges) {
                usleep(intval($interval * 1000000));
            }
        }

        $this->info('WAL event dispatch stopped');
    }

    /**
     * Auto-scan model directories, collect models implementing HasWalHandler.
     *
     * One table may have multiple handlers (e.g. Question + OrgQuestion sharing a table),
     * each handler will receive the same WAL change IDs independently.
     *
     * @return array<string, array<int, array{handler: HasWalHandler, keyName: string}>>
     */
    protected function discoverWalHandlers(): array
    {
        $handlers = [];
        $paths = $this->getModelPaths();

        foreach ($paths as $dir => $namespace) {
            if (!is_dir($dir)) {
                $this->warn(sprintf('Model path does not exist: %s', $dir));
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                /** @var \SplFileInfo $file */
                if ('php' !== $file->getExtension()) {
                    continue;
                }

                $pathname = str_replace('\\', '/', $file->getPathname());
                $baseDir = str_replace('\\', '/', $dir);
                $relativePath = substr($pathname, strlen($baseDir) + 1);
                $className = $namespace.str_replace('/', '\\', substr($relativePath, 0, -4));

                if (!class_exists($className)) {
                    continue;
                }

                try {
                    $reflection = new ReflectionClass($className);
                    if (!$reflection->isInstantiable()) {
                        continue;
                    }
                    if (!$reflection->implementsInterface(HasWalHandler::class)) {
                        continue;
                    }
                } catch (Throwable $e) {
                    continue;
                }

                /** @var HasWalHandler $instance */
                $instance = new $className();
                $keyName = method_exists($instance, 'getKeyName') ? $instance->getKeyName() : 'id';

                $handlers[$instance->getTable()][] = [
                    'handler' => $instance,
                    'keyName' => $keyName,
                ];
            }
        }

        return $handlers;
    }

    /**
     * Parse --model-path options into [absolutePath => namespace] map.
     *
     * @return array<string, string>
     */
    protected function getModelPaths(): array
    {
        $paths = $this->option('model-path');

        if (empty($paths)) {
            return [app_path('Models') => 'App\\Models\\'];
        }

        $result = [];
        foreach ($paths as $path) {
            if (false !== strpos($path, ':')) {
                list($dir, $ns) = explode(':', $path, 2);
            } else {
                $dir = $path;
                $ns = str_replace('/', '\\', ucfirst($dir));
            }

            $absDir = base_path($dir);
            $result[$absDir] = rtrim($ns, '\\').'\\';
        }

        return $result;
    }

    /**
     * Build partition table → parent table mapping from pg_inherits.
     *
     * @return array<string, string>
     */
    protected function buildPartitionMap(): array
    {
        try {
            $results = $this->getConnection()->select(
                "SELECT child_cls.relname AS child_table, parent_cls.relname AS parent_table
                 FROM pg_inherits
                 JOIN pg_class child_cls ON pg_inherits.inhrelid = child_cls.oid
                 JOIN pg_class parent_cls ON pg_inherits.inhparent = parent_cls.oid
                 WHERE child_cls.relkind = 'r'"
            );

            $map = [];
            foreach ($results as $row) {
                $map[$row->child_table] = $row->parent_table;
            }

            return $map;
        } catch (Throwable $e) {
            $this->warn(sprintf('Failed to build partition map: %s', $e->getMessage()));

            return [];
        }
    }

    /**
     * Resolve table name through partition map.
     */
    protected function resolveTable(string $table, array $partitionMap): string
    {
        return isset($partitionMap[$table]) ? $partitionMap[$table] : $table;
    }

    /**
     * @return string auto|advance|peek
     */
    protected function getMode(): string
    {
        $mode = $this->option('mode');
        if (in_array($mode, ['auto', 'advance', 'peek'], true)) {
            return $mode;
        }

        return 'advance';
    }

    protected function pollChanges(string $slot, array $handlers, int $batch, array $partitionMap): bool
    {
        $connection = $this->getConnection();
        $mode = $this->getMode();

        /** auto: get_changes (read=consume), advance/peek: peek_changes (read-only) */
        $function = 'auto' === $mode
            ? 'pg_logical_slot_get_changes'
            : 'pg_logical_slot_peek_changes';

        /**
         * Capture WAL LSN before peek so we have a safe advance target.
         *
         * In shared PG instances, other databases' WAL pushes pg_current_wal_lsn() forward
         * while this slot stays put (no changes for our DB), causing ever-growing phantom lag.
         * When peek returns empty we advance to this pre-peek LSN — guaranteed to not skip
         * any changes because peek already confirmed nothing exists up to at least this point.
         */
        $prePeekLsn = null;
        if ('advance' === $mode) {
            try {
                $row = $connection->selectOne('SELECT pg_current_wal_lsn() AS lsn');
                $prePeekLsn = null !== $row ? $row->lsn : null;
            } catch (Throwable $e) {
                /** best-effort */
            }
        }

        $results = $connection->select(
            sprintf('SELECT lsn, data FROM %s(?, NULL, ?)', $function),
            [$slot, $batch]
        );

        if (empty($results)) {
            if (null !== $prePeekLsn) {
                try {
                    $connection->statement('SELECT pg_replication_slot_advance(?, ?)', [$slot, $prePeekLsn]);
                } catch (Throwable $e) {
                    /** best-effort: advance failure does not affect normal flow */
                }
            }

            return false;
        }

        /** @var array<string, array<int, int|string>> $tableIds */
        $tableIds = [];
        $lastLsn = null;

        foreach ($results as $row) {
            $lastLsn = $row->lsn;

            $payload = json_decode($row->data, true);

            if (!is_array($payload)) {
                $this->warn(sprintf('JSON decode failed at LSN %s: %s', $row->lsn, substr($row->data, 0, 200)));
                continue;
            }

            if (empty($payload['change'])) {
                continue;
            }

            foreach ($payload['change'] as $change) {
                $table = isset($change['table']) ? $change['table'] : null;
                if (null === $table) {
                    continue;
                }

                $table = $this->resolveTable($table, $partitionMap);

                if (!isset($handlers[$table])) {
                    continue;
                }

                /** Use the first handler's keyName for extraction (same physical table, same PK) */
                $keyName = $handlers[$table][0]['keyName'];
                $id = $this->extractPrimaryKey($change, $keyName);
                if (null === $id) {
                    continue;
                }

                $tableIds[$table][] = $id;
            }
        }

        /** Dispatch to every handler registered on each table */
        foreach ($tableIds as $table => $ids) {
            $uniqueIds = array_values(array_unique($ids));

            foreach ($handlers[$table] as $meta) {
                $this->getEventsDispatcher()->dispatch(
                    new WalChangesDetected($meta['handler'], $uniqueIds)
                );

                $this->line(sprintf('[%s] %s (key: %s) [%s]', date('Y-m-d H:i:s'), get_class($meta['handler']), $meta['keyName'], implode(',', $uniqueIds)));
            }
        }

        /** advance mode: manually advance slot after successful dispatch */
        if ('advance' === $mode && null !== $lastLsn) {
            try {
                $connection->statement('SELECT pg_replication_slot_advance(?, ?)', [$slot, $lastLsn]);
            } catch (Throwable $e) {
                /** Slot already advanced (e.g. by another process), safe to ignore */
                $this->warn(sprintf('Slot advance warning (LSN %s): %s', $lastLsn, $e->getMessage()));
            }
        }

        return !empty($tableIds);
    }

    /**
     * Extract primary key value from a wal2json change record.
     *
     * #6 支持任意类型主键（int/string/uuid），不再硬限 int
     *
     * @param array  $change
     * @param string $keyName
     *
     * @return int|string|null
     */
    protected function extractPrimaryKey(array $change, string $keyName)
    {
        $kind = isset($change['kind']) ? $change['kind'] : null;

        if ('delete' === $kind) {
            $keyNames = isset($change['oldkeys']['keynames']) ? $change['oldkeys']['keynames'] : [];
            $keyValues = isset($change['oldkeys']['keyvalues']) ? $change['oldkeys']['keyvalues'] : [];
            $index = array_search($keyName, $keyNames);
            if (false !== $index && isset($keyValues[$index])) {
                return $keyValues[$index];
            }

            return null;
        }

        $columnNames = isset($change['columnnames']) ? $change['columnnames'] : [];
        $columnValues = isset($change['columnvalues']) ? $change['columnvalues'] : [];
        $index = array_search($keyName, $columnNames);
        if (false !== $index && isset($columnValues[$index])) {
            return $columnValues[$index];
        }

        return null;
    }

    protected function getSlotName(): string
    {
        $slot = $this->option('slot');
        if (!empty($slot)) {
            return $slot;
        }

        $appName = config('app.name') ?: 'app';
        $appEnv = config('app.env') ?: 'production';

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $appName.'_'.$appEnv));
        $slug = preg_replace('/_+/', '_', trim($slug, '_'));

        return $slug.'_wal_event';
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    protected function getConnection()
    {
        $name = $this->option('connection');

        return app('db')->connection($name ?: null);
    }

    protected function registerSignalHandlers(): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->shouldRun = false;
        });
        pcntl_signal(SIGINT, function () {
            $this->shouldRun = false;
        });
    }

    /**
     * @param int $memoryLimit MB
     */
    protected function isMemoryExceeded($memoryLimit): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }

    /**
     * Flush Telescope entries for long-running CLI process.
     *
     * Telescope only flushes on $app->terminating(), which never fires for
     * persistent processes. Call store() explicitly after each poll cycle.
     */
    protected function flushTelescopeEntries(): void
    {
        if (!class_exists('Laravel\Telescope\Telescope')) {
            return;
        }

        if (!\Laravel\Telescope\Telescope::isRecording()) {
            return;
        }

        try {
            \Laravel\Telescope\Telescope::store(
                app('Laravel\Telescope\Contracts\EntriesRepository')
            );
        } catch (Throwable $e) {
            /** best-effort: Telescope 刷入失败不影响 WAL 处理 */
        }
    }

    protected function reconnectDatabase(): void
    {
        try {
            $this->getConnection()->reconnect();
            $this->info('Database reconnected');
        } catch (Throwable $e) {
            $this->error(sprintf('Database reconnect failed: %s', $e->getMessage()));
        }
    }

    protected function ensureSlotExists(string $slot): void
    {
        $connection = $this->getConnection();

        $exists = $connection->selectOne(
            'SELECT 1 FROM pg_replication_slots WHERE slot_name = ?',
            [$slot]
        );

        if (null !== $exists) {
            return;
        }

        $this->info(sprintf('Slot [%s] does not exist, creating...', $slot));
        $connection->statement(
            "SELECT pg_create_logical_replication_slot(?, 'wal2json')",
            [$slot]
        );
        $this->info(sprintf('Slot [%s] created successfully', $slot));
    }
}
