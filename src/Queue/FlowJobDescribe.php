<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/21
 * Time: 22:14.
 */

namespace HughCube\Laravel\Knight\Queue;

use HughCube\Laravel\Knight\Traits\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\DatabaseQueue;

class FlowJobDescribe
{
    use Container;

    protected $connection;

    protected $queue;

    protected $id;

    public function __construct($connection, $queue, $id)
    {
        $this->connection = $connection;
        $this->queue = $queue;
        $this->id = $id;
    }

    public function getConnection(): Queue
    {
        return $this->getQueueManager()->connection($this->connection);
    }

    public function getDatabaseConnection(): ?DatabaseQueue
    {
        $connection = $this->getConnection();
        if ($connection instanceof DatabaseQueue) {
            return $connection;
        }

        return null;
    }

    public function isDatabaseConnection(): bool
    {
        return $this->getDatabaseConnection() instanceof DatabaseQueue;
    }
}
