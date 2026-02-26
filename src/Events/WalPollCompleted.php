<?php

namespace HughCube\Laravel\Knight\Events;

class WalPollCompleted
{
    /**
     * @var bool
     */
    public $hasChanges;

    /**
     * @param bool $hasChanges
     */
    public function __construct(bool $hasChanges)
    {
        $this->hasChanges = $hasChanges;
    }
}
