<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Carbon\Carbon;
use Cron\CronExpression;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Traits\MultipleHandler;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Finder\Finder;
use Throwable;

class ScheduleJob extends Job
{
    use MultipleHandler;

    private $scheduleJobCount = 0;

    /**
     * @return void
     * @throws Throwable
     *
     */
    protected function action(): void
    {
        $this->triggerMultipleHandlers();

        if (0 >= $this->scheduleJobCount) {
            $this->info('No scheduled jobs are ready to run.');
        }
    }

    protected function incrementScheduledJobCount(): int
    {
        return ++$this->scheduleJobCount;
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
        return (new CronExpression($expression))->isDue($this->getJobStartedAt()->toDateTimeString());
    }

    /**
     * @param  string|array  $name
     * @param  string|array|null  $in
     * @param  string|null  $basePath
     *
     * @return array<integer, object>
     */
    protected function parseDirJobs($name = '*.php', $in = null, string $basePath = null): array
    {
        $in = $in ?: app_path('Jobs');
        $basePath = $basePath ?: base_path();
        $files = (new Finder())->files()->in($in)->name($name);

        $jobs = [];
        foreach ($files as $file) {
            $class = Str::replaceFirst($basePath, '', $file->getRealPath());
            $class = trim($class, DIRECTORY_SEPARATOR);
            $class = rtrim($class, '.php');
            $class = strtr($class, ['/' => '\\']);
            $class = Str::ucfirst($class);

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

        return $jobs;
    }

    protected function prepareJob($job)
    {
        return $job;
    }

    protected function pushJob($job)
    {
        $start = Carbon::now();
        $id = $this->getDispatcher()->dispatch($this->prepareJob($job));
        $end = Carbon::now();

        $this->info(sprintf(
            '[%s] push job success, job: %s, id: %s, duration: %sms, delays: %sms',
            $this->incrementScheduledJobCount(),
            Str::afterLast(get_class($job), '\\'),
            is_scalar($id) ? $id : '',
            $start->diffInRealMilliseconds($end),
            $this->getDelays()
        ));

        return $id;
    }

    /**
     * @param  string  $expression
     * @param  callable|Job|object  $job
     *
     * @return void
     */
    protected function pushJobIfDue(string $expression, $job)
    {
        if ($this->isDue($expression)) {
            $this->pushJob(is_callable($job) ? $job() : $job);
        }
    }

    /**
     * @param  mixed  $job
     *
     * @return mixed
     */
    protected function fireJob($job)
    {
        $start = Carbon::now();
        $result = $this->getDispatcher()->dispatchSync($this->prepareJob($job));
        $end = Carbon::now();

        $this->info(sprintf(
            '[%s] fire job success, job: %s, duration: %sms, delays: %sms',
            $this->incrementScheduledJobCount(),
            Str::afterLast(get_class($job), '\\'),
            $start->diffInRealMilliseconds($end),
            $this->getDelays()
        ));

        return $result;
    }

    /**
     * @param  string  $expression
     * @param  callable|Job|object  $job
     *
     * @return void
     */
    protected function fireJobIfDue(string $expression, $job)
    {
        if ($this->isDue($expression)) {
            $this->fireJob(is_callable($job) ? $job() : $job);
        }
    }
}
