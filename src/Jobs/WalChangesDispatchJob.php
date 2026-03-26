<?php

namespace HughCube\Laravel\Knight\Jobs;

use HughCube\Laravel\Knight\Database\Wal\WalChangeRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * WAL 变更事件分发 Job。
 *
 * 接收单条 WalChangeRecord，dispatch 字符串事件 wal:{table}:{kind}。
 * Job 本身不做任何业务处理，仅负责将 WAL 变更记录转化为应用层可订阅的事件。
 *
 * 应用层通过 Event::listen() 订阅感兴趣的事件：
 * - Event::listen('wal:*', ClearCacheListener::class)       // 缓存清理
 * - Event::listen('wal:questions:update', SyncOtsListener::class) // OTS 同步
 *
 * 默认通过 sync 连接同步执行；生产环境可通过 --queue-connection=redis 推入队列。
 */
class WalChangesDispatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /** @var WalChangeRecord */
    private $record;

    public function __construct(WalChangeRecord $record)
    {
        $this->record = $record;
    }

    public function handle(EventsDispatcher $dispatcher): void
    {
        $event = sprintf('wal:%s:%s', $this->getRecord()->getTable(), $this->getRecord()->getKind());

        $dispatcher->dispatch($event, [$this->getRecord()]);
    }

    /** @return WalChangeRecord */
    public function getRecord(): WalChangeRecord
    {
        return $this->record;
    }
}
