<?php

namespace HughCube\Laravel\Knight\Traits;

use Illuminate\Support\Carbon;

/**
 * 时间范围判断 trait
 *
 * 提供基于开始时间和结束时间的状态判断方法
 * 需要实现 HasTimeRange 接口的 getStartedAt() 和 getEndedAt() 方法
 *
 * @see \HughCube\Laravel\Knight\Contracts\Support\HasTimeRange
 */
trait HasTimeRangeTrait
{
    /**
     * 是否已开始
     *
     * @return bool
     */
    public function isStarted()
    {
        return null !== $this->getStartedAt() && $this->getStartedAt() <= Carbon::now();
    }

    /**
     * 是否已结束
     *
     * @return bool
     */
    public function isEnded()
    {
        return null !== $this->getEndedAt() && $this->getEndedAt() <= Carbon::now();
    }

    /**
     * 是否在有效时间内（已开始且未结束）
     *
     * @return bool
     */
    public function isInProgress()
    {
        return $this->isStarted() && !$this->isEnded();
    }
}
