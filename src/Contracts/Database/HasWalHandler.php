<?php

namespace HughCube\Laravel\Knight\Contracts\Database;

interface HasWalHandler
{
    /**
     * @return string
     */
    public function getTable();

    /**
     * Called when model data has changed (created/updated/deleted).
     * Default: clear row cache. Override to add sync logic (e.g. OTS).
     *
     * Eloquent event path calls this automatically via model observers.
     * WAL event path does NOT call this automatically — subscribe to wal:{table}:{kind}
     * string events and call it in your Listener if needed.
     */
    public function onKnightModelChanged(): void;
}
