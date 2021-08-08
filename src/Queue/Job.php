<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 11:27 上午.
 */

namespace HughCube\Laravel\Knight\Queue;

use HughCube\Laravel\Knight\Support\GetOrSet;
use HughCube\Laravel\Knight\Support\Validation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use GetOrSet, Validation;

    /**
     * @var array
     */
    private $data;

    /**
     * Create a new job instance.
     *
     * @return void
     * @throws ValidationException
     */
    public function __construct(array $data)
    {
        $this->data = $this->validate($data);
    }

    /**
     * @param  string  $key
     * @param  null  $default
     *
     * @return mixed
     */
    protected function getValue(string $key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return static
     */
    protected function setValue(string $key, $value): Job
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    /**
     * @param  string  $message
     */
    protected function echo(string $message)
    {
        echo sprintf(
            '[%s][%s] %s %s: %s',
            Carbon::now()->format('Y-m-d H:i:s.u'),
            $this->job->getJobId(),
            str_pad('Processing:', 11),
            $this->job->resolveName(),
            $message
        );
    }
}
