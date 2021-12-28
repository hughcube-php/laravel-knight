<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Carbon\Carbon;
use Cron\CronExpression;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Support\MultipleHandler;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Date;
use Throwable;

class ScheduleJob extends Job
{
    use MultipleHandler;

    protected ?Carbon $startData = null;

    /**
     * @throws Throwable
     */
    protected function action(): void
    {
        $this->startData = Carbon::now();
        $this->triggerHandlers(true);
    }

    protected function getDelays(): int
    {
        return $this->startData->diffInRealMilliseconds(Carbon::now());
    }

    /**
     * 判断是否可以运行
     *
     * @param $expression
     * @return bool
     */
    protected function isDue($expression): bool
    {
        $date = Date::now();

        return (new CronExpression($expression))->isDue($date->toDateTimeString());
    }

    /**
     * push任务
     *
     * @param  Job  $job
     * @return void
     */
    protected function pushJob(Job $job)
    {
        $id = app(Dispatcher::class)->dispatch($job);
        $this->info(sprintf('job: %s, id:%s, delays:%sms', $this->getName($job), $id, $this->getDelays()));
    }

    /**
     * @param  string  $expression
     * @param  callable  $callable
     * @return void
     */
    protected function pushJobIfDue(string $expression, callable $callable)
    {
        if ($this->isDue($expression)) {
            $this->pushJob($callable());
        }
    }
}
