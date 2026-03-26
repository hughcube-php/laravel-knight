<?php

namespace HughCube\Laravel\Knight\Console\Commands;

use HughCube\Laravel\Knight\Console\Command;
use HughCube\Laravel\Knight\Contracts\Database\HasWalHandler;
use HughCube\Laravel\Knight\Database\Wal\WalChangeRecord;
use HughCube\Laravel\Knight\Jobs\WalChangesDispatchJob;
use Illuminate\Database\Connection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Throwable;

/**
 * 监听 PostgreSQL WAL (Write-Ahead Log) 变更并分发事件。
 *
 * ## 原理
 *
 * PostgreSQL 的逻辑复制机制通过 WAL 记录所有数据变更。本命令利用 wal2json 插件
 * 将 WAL 二进制数据解码为 JSON，从中提取表名、操作类型（INSERT/UPDATE/DELETE）、
 * 新旧列值等信息，封装为 WalChangeRecord 值对象，通过 WalChangesDispatchJob 以
 * 字符串事件（wal:{table}:{kind}）分发给应用层。
 *
 * ## 核心流程
 *
 * 1. **初始化**：创建/检查复制槽 → 扫描实现 HasWalHandler 的 Model → 构建分区表映射
 * 2. **主循环**：轮询 WAL 变更 → 解析 wal2json JSON → 构建 WalChangeRecord → 分发事件
 * 3. **终止**：内存超限 / 连续错误达上限 / 收到 SIGTERM|SIGINT 信号
 *
 * ## 消费模式 (--mode)
 *
 * - **advance** (默认，推荐)：peek_changes 读取 → 分发事件 → 手动 advance 推进槽位。
 *   事件分发失败不会丢失 WAL 数据，下次轮询可重试。
 * - **auto**：get_changes 读取即消费。最简单，但事件分发失败时 WAL 数据已被消费，无法重试。
 * - **peek**：只读不消费，用于调试。每次轮询都能看到相同数据。
 *
 * ## 事件分发 (--queue-connection / --queue)
 *
 * 每条 WAL 变更通过 WalChangesDispatchJob 分发字符串事件 wal:{table}:{kind}，
 * 应用层通过 Event::listen() 订阅事件并自行处理（缓存清理、同步等）。
 * Job 本身不做任何业务处理，仅负责事件转发。
 *
 * 通过 --queue-connection 和 --queue 控制 Job 的执行方式：
 * - 默认（sync）：在本命令进程内同步执行
 * - --queue-connection=redis --queue=wal-events：推入 redis 的 wal-events 队列，适合生产
 *
 * 注意：每条 WAL 变更产生一个独立的 Job。高吞吐场景（如批量 INSERT）会产生等量的
 * Job dispatch，生产环境建议使用 queue 模式异步处理。
 *
 * ## 分区表支持
 *
 * 启动时通过 pg_inherits 系统表自动构建 子表→父表 映射。WAL 中记录的是分区子表名
 * （如 orders_p0），命令自动解析为父表名（orders）查找对应的 Handler。
 * WalChangeRecord 同时保留原始子表名（partitionTable）和解析后的父表名（table）。
 *
 * ## 防幻影滞后 (Phantom Lag Prevention)
 *
 * 共享 PG 实例中，其他数据库的 WAL 活动会推进全局 LSN，但本数据库的复制槽不动，
 * 导致监控指标显示持续增长的"假性滞后"。advance 模式下，每次 peek 前记录当前 LSN，
 * 若 peek 结果为空则 advance 到该 LSN，消除假性滞后。
 *
 * ## wal2json 参数透传 (--wal2json-params)
 *
 * 支持向 wal2json 插件传递任意参数（key=value 格式），常用于控制输出列：
 * - filter-columns=content,body  排除大字段，降低内存消耗
 * - add-columns=id,status        只包含指定列
 *
 * ## 错误恢复
 *
 * 轮询异常时采用指数退避：1s → 2s → 4s → ... → 60s（上限），自动重连数据库并
 * 重新检查复制槽。连续错误达到 --max-errors 上限时自动停止。
 *
 * ## 使用示例
 *
 * ```bash
 * # 基本用法（默认 advance 模式 + sync 同步执行）
 * php artisan wal:event-dispatch
 *
 * # 生产环境推荐：推入 redis 队列
 * php artisan wal:event-dispatch --queue-connection=redis --queue=wal-events
 *
 * # 过滤大字段，降低内存消耗
 * php artisan wal:event-dispatch --wal2json-params="filter-columns=content,raw_body"
 *
 * # 自定义复制槽 + 更快的轮询间隔
 * php artisan wal:event-dispatch --slot=my_app_wal --interval=0.5 --batch=2000
 *
 * # 调试模式：只读不消费
 * php artisan wal:event-dispatch --mode=peek
 *
 * # 自定义 Model 扫描路径
 * php artisan wal:event-dispatch --model-path="app/Models:App\\Models" --model-path="app/Domain:App\\Domain"
 * ```
 *
 * ## 应用层接入
 *
 * 1. Model 实现 HasWalHandler 接口（getTable + onKnightModelChanged）
 * 2. 注册字符串事件 Listener（如 Event::listen('wal:questions:update', MyListener::class)）
 * 3. Listener 通过 WalChangeRecord::makeModel() 获取填充好的 Model 实例
 *
 * 注意：缓存清理等业务逻辑需应用层自行在 Listener 中处理，Job 仅负责事件分发。
 *
 * @see WalChangeRecord      WAL 变更记录值对象，包含 makeModel() 方法
 * @see WalChangesDispatchJob 事件分发 Job（sync/queue 统一入口）
 * @see HasWalHandler         Model 需实现的接口
 */
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
        {--model-path=* : Model scanning paths, can be specified multiple times (default: app/Models)}
        {--wal2json-params=* : wal2json plugin parameters, e.g. --wal2json-params="filter-columns=content,body"}
        {--queue= : Queue name for WalChangesDispatchJob}
        {--queue-connection=sync : Queue connection for WalChangesDispatchJob, e.g. sync, redis}';

    protected $description = 'Monitor PostgreSQL WAL changes and dispatch corresponding events';

    /**
     * @var bool
     */
    protected $shouldRun = true;

    /**
     * @var int Consecutive error count for exponential backoff.
     */
    protected $errorStreak = 0;

    /**
     * 主入口：初始化 → 主循环 → 清理退出。
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $slot = $this->getSlotName();
        $interval = max(0.01, floatval($this->option('interval')));
        $batch = max(1, intval($this->option('batch')));

        $this->ensureSlotExists($slot);
        $this->registerSignalHandlers();

        $handlers = $this->discoverWalHandlers();
        $partitionMap = $this->buildPartitionMap();

        $handlerCount = array_sum(array_map('count', $handlers));

        $memoryLimit = max(1, intval($this->option('memory')));
        $maxErrors = max(0, intval($this->option('max-errors')));

        $this->info(sprintf(
            'WAL event dispatch started, slot: %s, mode: %s, queue: %s/%s, interval: %.2fs, batch: %d, memory: %dMB, max-errors: %s, tables: %d, handlers: %d, partitions: %d',
            $slot,
            $this->getMode(),
            $this->option('queue-connection'),
            $this->option('queue') ?: 'default',
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

                /** 指数退避: 1s → 2s → 4s → ... → 60s 上限 */
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
     * 扫描 Model 目录，收集实现 HasWalHandler 接口的 Model。
     *
     * 同一张表可以有多个 Handler（如 Question 和 OrgQuestion 共享 questions 表），
     * 每个 Handler 独立接收相同的 WAL 变更。
     *
     * 返回格式：[ 'table_name' => [ ['handler' => Model, 'keyName' => 'id'], ... ] ]
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
                $className = $namespace . str_replace('/', '\\', substr($relativePath, 0, -4));

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
     * 解析 --model-path 选项为 [绝对路径 => 命名空间] 映射。
     *
     * 格式：--model-path="app/Models:App\\Models"（冒号分隔路径和命名空间）
     * 省略命名空间时，自动从目录路径推导。
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
            $result[$absDir] = rtrim($ns, '\\') . '\\';
        }

        return $result;
    }

    /**
     * 通过 pg_inherits 系统表构建 分区子表 → 父表 映射。
     *
     * PostgreSQL 分区表的 WAL 记录使用分区子表名（如 orders_p0），
     * 需要映射到父表名（orders）才能找到对应的 Handler。
     *
     * @return array<string, string> ['orders_p0' => 'orders', ...]
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
     * 通过分区映射解析表名。非分区表原样返回。
     */
    protected function resolveTable(string $table, array $partitionMap): string
    {
        return $partitionMap[$table] ?? $table;
    }

    /**
     * 获取 WAL 消费模式。
     *
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

    /**
     * 单次轮询：读取 WAL → 解析变更 → 构建 WalChangeRecord → 分发事件 → 推进槽位。
     *
     * 流程：
     * 1. 通过 wal2json 读取 WAL 变更（peek 或 get，取决于 mode）
     * 2. 解析 JSON，按表名分组构建 WalChangeRecord 数组
     * 3. 对每条记录 dispatch WalChangesDispatchJob，Job 内 dispatch 字符串事件
     * 4. advance 模式下，推进槽位到最后处理的 LSN
     *
     * @param string $slot 复制槽名称
     * @param array $handlers Handler 映射 [table => [{handler, keyName}, ...]]
     * @param int $batch 单次最大变更数
     * @param array $partitionMap 分区表映射 [child => parent]
     *
     * @return bool 是否有变更
     */
    protected function pollChanges(string $slot, array $handlers, int $batch, array $partitionMap): bool
    {
        $connection = $this->getConnection();
        $mode = $this->getMode();

        /** auto 模式用 get_changes（读即消费），advance/peek 用 peek_changes（只读） */
        $function = 'auto' === $mode
            ? 'pg_logical_slot_get_changes'
            : 'pg_logical_slot_peek_changes';

        /**
         * 防幻影滞后：peek 前记录当前 WAL LSN。
         *
         * 共享 PG 实例中，其他数据库的 WAL 会推进 pg_current_wal_lsn()，
         * 而本数据库的复制槽不动（无变更），导致监控指标显示持续增长的假性滞后。
         * peek 结果为空时，advance 到此 LSN——peek 已确认此 LSN 之前无遗漏数据。
         */
        $prePeekLsn = null;
        if ('advance' === $mode) {
            try {
                $prePeekLsn = optional($connection->selectOne('SELECT pg_current_wal_lsn() AS lsn'))->lsn;
            } catch (Throwable $e) {
                /** best-effort */
            }
        }

        /**
         * 调用 wal2json 读取 WAL 变更。
         *
         * SQL: SELECT lsn, data FROM pg_logical_slot_peek_changes('slot', NULL, 1000, 'filter-columns', 'content')
         * 第二个参数 NULL = 读到最新 LSN；第三个参数 = 最大行数；后续为 wal2json 插件参数（key, value 交替）。
         */
        $sql = sprintf('SELECT lsn, data FROM %s(?, NULL, ?%s)', $function, $this->buildWal2jsonParamPlaceholders());
        $bindings = array_merge([$slot, $batch], $this->buildWal2jsonParamBindings());
        $results = $connection->select($sql, $bindings);

        /** 无变更：advance 模式下推进到 prePeekLsn 消除幻影滞后 */
        if (empty($results)) {
            if (null !== $prePeekLsn) {
                try {
                    $connection->statement('SELECT pg_replication_slot_advance(?, ?)', [$slot, $prePeekLsn]);
                } catch (Throwable $e) {
                    /** best-effort */
                }
            }

            return false;
        }

        /** @var WalChangeRecord[] $records */
        $records = [];
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
                $rawTable = $change['table'] ?? null;
                if (null === $rawTable) {
                    continue;
                }

                $resolvedTable = $this->resolveTable($rawTable, $partitionMap);
                if (!isset($handlers[$resolvedTable])) {
                    continue;
                }

                /**
                 * 为每个 Handler 通过工厂方法构建 WalChangeRecord 并 dispatch Job。
                 * 同一张表可能有多个 Handler（如 Question + OrgQuestion 共享 questions 表）。
                 * 每个 Handler 使用自己的 keyName，因为同表不同 Model 可能有不同主键。
                 */
                foreach ($handlers[$resolvedTable] as $meta) {
                    $record = WalChangeRecord::fromWal2json($change, $resolvedTable, $rawTable, $meta['keyName'], get_class($meta['handler']));
                    if (null === $record) {
                        continue;
                    }

                    WalChangesDispatchJob::dispatch($record)
                        ->onConnection($this->option('queue-connection'))
                        ->onQueue($this->option('queue'));

                    $records[] = $record;
                }
            }
        }

        if (!empty($records)) {
            $summary = $this->buildChangeSummary($records);
            $this->line(sprintf('[%s] %s', date('Y-m-d H:i:s'), $summary));
        }

        /** advance 模式：事件分发成功后，手动推进槽位到最后处理的 LSN */
        if ('advance' === $mode && null !== $lastLsn) {
            try {
                $connection->statement('SELECT pg_replication_slot_advance(?, ?)', [$slot, $lastLsn]);
            } catch (Throwable $e) {
                /** 槽位可能已被其他进程推进，可安全忽略 */
                $this->warn(sprintf('Slot advance warning (LSN %s): %s', $lastLsn, $e->getMessage()));
            }
        }

        return !empty($records);
    }

    /**
     * 构建 wal2json 插件参数的 SQL 占位符。
     *
     * wal2json 参数以 key, value 交替传入 PG 函数，每个参数需要两个占位符。
     * 例如 --wal2json-params="filter-columns=content" 生成 ", ?, ?"。
     *
     * @return string 如 ", ?, ?, ?, ?" 或 ""
     */
    protected function buildWal2jsonParamPlaceholders(): string
    {
        $params = $this->option('wal2json-params');
        if (empty($params)) {
            return '';
        }

        return str_repeat(', ?, ?', count($params));
    }

    /**
     * 将 --wal2json-params 选项解析为 SQL 绑定参数数组。
     *
     * 每个 "key=value" 拆分为两个绑定值 [key, value]，无等号的视为 [key, '']。
     *
     * @return array 如 ['filter-columns', 'content,body', 'add-tables', 'users']
     */
    protected function buildWal2jsonParamBindings(): array
    {
        $params = $this->option('wal2json-params');
        if (empty($params)) {
            return [];
        }

        $bindings = [];
        foreach ($params as $param) {
            $parts = explode('=', $param, 2);
            $bindings[] = $parts[0];
            $bindings[] = $parts[1] ?? '';
        }

        return $bindings;
    }

    /**
     * 按表名+操作类型汇总变更记录数，生成可读摘要。
     *
     * @param WalChangeRecord[] $changes
     *
     * @return string 如 "users: 3 inserts, 2 updates; orders: 1 delete"
     */
    protected function buildChangeSummary(array $changes): string
    {
        /** @var array<string, array<string, int>> $tableCounts */
        $tableCounts = [];
        foreach ($changes as $record) {
            $table = $record->getTable();
            $kind = $record->getKind();
            if (!isset($tableCounts[$table][$kind])) {
                $tableCounts[$table][$kind] = 0;
            }
            $tableCounts[$table][$kind]++;
        }

        $tableParts = [];
        foreach ($tableCounts as $table => $kinds) {
            $kindParts = [];
            foreach ($kinds as $kind => $count) {
                $kindParts[] = sprintf('%d %s%s', $count, $kind, $count > 1 ? 's' : '');
            }
            $tableParts[] = sprintf('%s: %s', $table, implode(', ', $kindParts));
        }

        return implode('; ', $tableParts);
    }

    /**
     * 生成复制槽名称。
     *
     * 指定 --slot 时直接使用，否则自动生成：{app_name}_{app_env}_wal_event，
     * 非字母数字字符替换为下划线。
     */
    protected function getSlotName(): string
    {
        $slot = $this->option('slot');
        if (!empty($slot)) {
            return $slot;
        }

        $appName = config('app.name') ?: 'app';
        $appEnv = config('app.env') ?: 'production';

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $appName . '_' . $appEnv));
        $slug = preg_replace('/_+/', '_', trim($slug, '_'));

        return $slug . '_wal_event';
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return app('db')->connection($this->option('connection') ?: null);
    }

    /**
     * 注册 SIGTERM/SIGINT 信号处理器，实现优雅关闭。
     */
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
     * 长驻进程手动刷入 Telescope 条目。
     *
     * Telescope 仅在 $app->terminating() 时刷入，长驻进程永远不会触发该事件。
     * 每次轮询成功后显式调用 store()，确保 Telescope 数据不会积压在内存中。
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
            \Laravel\Telescope\Telescope::store(app('Laravel\Telescope\Contracts\EntriesRepository'));
        } catch (Throwable $e) {
            /** best-effort */
        }
    }

    /**
     * 重连数据库。轮询异常时调用，尝试恢复连接。
     */
    protected function reconnectDatabase(): void
    {
        try {
            $this->getConnection()->reconnect();
            $this->info('Database reconnected');
        } catch (Throwable $e) {
            $this->error(sprintf('Database reconnect failed: %s', $e->getMessage()));
        }
    }

    /**
     * 确保复制槽存在，不存在则创建（使用 wal2json 插件）。
     */
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
