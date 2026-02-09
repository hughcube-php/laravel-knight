<?php

namespace HughCube\Laravel\Knight\Contracts\Support;

use Illuminate\Support\Carbon;

/**
 * 包含时间范围的接口
 *
 * 用于标识对象具有开始时间和结束时间
 */
interface HasTimeRange
{
    /**
     * 获取开始时间
     *
     * @return Carbon|null
     */
    public function getStartedAt();

    /**
     * 获取结束时间
     *
     * @return Carbon|null
     */
    public function getEndedAt();

    /**
     * 是否已开始
     *
     * @return bool
     */
    public function isStarted();

    /**
     * 是否已结束
     *
     * @return bool
     */
    public function isEnded();

    /**
     * 是否在有效时间内（已开始且未结束）
     *
     * @return bool
     */
    public function isInProgress();
}
