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
     * Both Eloquent event path and WAL event path converge on this method.
     */
    public function onKnightModelChanged(): void;
}
