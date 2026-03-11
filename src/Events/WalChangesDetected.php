<?php

namespace HughCube\Laravel\Knight\Events;

use HughCube\Laravel\Knight\Contracts\Database\HasWalHandler;

class WalChangesDetected
{
    /**
     * @var HasWalHandler
     */
    public $handler;

    /**
     * @var array<int|string>
     */
    public $ids;

    /**
     * @param HasWalHandler     $handler
     * @param array<int|string> $ids
     */
    public function __construct(HasWalHandler $handler, array $ids)
    {
        $this->handler = $handler;
        $this->ids = $ids;
    }
}
