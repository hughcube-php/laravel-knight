<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/21
 * Time: 22:11
 */

namespace HughCube\Laravel\Knight\Contracts\Queue;

use HughCube\Laravel\Knight\Queue\FlowJobDescribe;

interface FromFlowJob
{
    public function setFlowJobDescribe(FlowJobDescribe $describe);

    public function isDelayDeleteFlowJob(): bool;
}
