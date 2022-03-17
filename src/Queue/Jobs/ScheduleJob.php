<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Carbon\Carbon;
use Cron\CronExpression;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Traits\MultipleHandler;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;
use Throwable;

class ScheduleJob extends Job
{
    use MultipleHandler;

    /**
     * @var Carbon
     */
    private $jobStartedAt = null;

    /**
     * @return void
     * @throws Throwable
     *
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
     * @param  string  $expression
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
     * @param  object  $job
     *
     * @return void
     */
    protected function pushJob(object $job)
    {
        $id = app(Dispatcher::class)->dispatch($job);
        $this->info(sprintf('job: %s, id:%s, delays:%sms', $this->getName($job), $id, $this->getDelays()));
    }

    /**
     * @param  string  $expression
     * @param  callable|Job  $job
     *
     * @return void
     */
    protected function pushJobIfDue(string $expression, $job)
    {
        if ($this->isDue($expression)) {
            $this->pushJob((is_callable($job) ? $job() : $job));
        }
    }

    /**
     * @param  string|array  $name
     * @param  string|array|null  $in
     * @param  string|null  $basePath
     * @return void
     */
    protected function pushDirJobs($name = '*.php', $in = null, string $basePath = null)
    {
        $in = $in ?: app_path('Jobs');
        $basePath = $basePath ?: base_path();
        $files = (new Finder)->files()->in($in)->name($name);

        $jobs = [];
        foreach ($files as $file) {
            $class = trim(Str::replaceFirst($basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

            try {
                $reflection = new ReflectionClass($class);
            } catch (ReflectionException $e) {
                continue;
            }

            if (!$reflection->isInstantiable()) {
                continue;
            }

            $jobs[] = new $class();
        }

        foreach ($jobs as $job) {
            $this->pushJob($job);
        }
    }

    /**
     * @param  string  $expression
     * @param  string|array  $name
     * @param  string|array|null  $in
     * @param  string|null  $basePath
     * @return void
     */
    protected function pushDirJobsIfDue(string $expression, $name = '*.php', $in = null, string $basePath = null)
    {
        if ($this->isDue($expression)) {
            $this->pushDirJobs($name, $in, $basePath);
        }
    }
}
