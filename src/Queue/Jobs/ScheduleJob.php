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

    /**
     * @var Carbon
     */
    private $jobStartedAt = null;

    /**
     * @throws Throwable
     *
     * @return void
     */
    protected function action(): void
    {
        $this->jobStartedAt = Carbon::now();
        $this->triggerHandlers(true);
    }

    protected function isStopHandlerResults($results, Throwable $exception = null): bool
    {
        return false;
    }

    /**
     * @return int
     */
    protected function getDelays(): int
    {
        return $this->jobStartedAt->diffInRealMilliseconds(Carbon::now());
    }

    /**
     * 判断是否可以运行.
     *
     * @param string $expression
     *
     * @return bool
     */
    protected function isDue(string $expression): bool
    {
        $date = Date::now();

        return (new CronExpression($expression))->isDue($date->toDateTimeString());
    }

    /**
     * push任务
     *
     * @param Job $job
     *
     * @return void
     */
    protected function pushJob(Job $job)
    {
        $id = app(Dispatcher::class)->dispatch($job);
        $this->info(sprintf('job: %s, id:%s, delays:%sms', $this->getName($job), $id, $this->getDelays()));
    }

    /**
     * @param string       $expression
     * @param callable|Job $job
     *
     * @return void
     */
    protected function pushJobIfDue(string $expression, $job)
    {
        if ($this->isDue($expression)) {
            $this->pushJob((is_callable($job) ? $job() : $job));
        }
    }
}
