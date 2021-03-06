<?php

namespace HughCube\Laravel\Knight\Queue\Jobs;

use Carbon\Carbon;
use Cron\CronExpression;
use HughCube\Laravel\Knight\Queue\Job;
use HughCube\Laravel\Knight\Traits\MultipleHandler;
use Illuminate\Contracts\Bus\Dispatcher;
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

    protected function getJobStartedAt(): Carbon
    {
        if (!$this->jobStartedAt instanceof Carbon) {
            $this->jobStartedAt = Carbon::now();
        }

        return $this->jobStartedAt;
    }

    /**
     * @return int
     */
    protected function getDelays(): int
    {
        return $this->getJobStartedAt()->diffInRealMilliseconds(Carbon::now());
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
        return (new CronExpression($expression))->isDue($this->getJobStartedAt()->toDateTimeString());
    }

    /**
     * @param string|array      $name
     * @param string|array|null $in
     * @param string|null       $basePath
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

    protected function getDispatcher(): Dispatcher
    {
        return app(Dispatcher::class);
    }

    protected function prepareJob($job)
    {
        return $job;
    }

    protected function pushJob($job)
    {
        $id = $this->getDispatcher()->dispatch($this->prepareJob($job));

        $name = Str::afterLast(get_class($job), '\\');
        $this->info(sprintf('job: %s, id:%s, delays:%sms', $name, (is_scalar($id) ? $id : ''), $this->getDelays()));
    }

    /**
     * @param string              $expression
     * @param callable|Job|object $job
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
     * @param mixed $job
     *
     * @return mixed
     */
    protected function fireJob($job)
    {
        return $this->getDispatcher()->dispatchNow($this->prepareJob($job));
    }

    /**
     * @param string              $expression
     * @param callable|Job|object $job
     *
     * @return void
     */
    protected function fireJobIfDue(string $expression, $job)
    {
        if ($this->isDue($expression)) {
            $this->fireJob((is_callable($job) ? $job() : $job));
        }
    }
}
