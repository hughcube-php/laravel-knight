<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/3/17
 * Time: 11:35.
 */

namespace HughCube\Laravel\Knight\Events;

use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Routing\Action;

class ActionProcessed
{
    protected $action = null;

    public function __construct($action)
    {
        $this->action = $action;
    }

    /**
     * @return Job|Action|null
     *
     * @phpstan-ignore-next-line
     */
    protected function getAction()
    {
        return $this->action;
    }
}
