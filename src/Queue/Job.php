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
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Job implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use GetOrSet;
    use Validation;

    /**
     * @var OutputInterface
     */
    private static $output;

    /**
     * @var array
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $this->validate($data);
    }

    /**
     * @param string $key
     * @param null   $default
     *
     * @return mixed
     */
    protected function getValue($key, $default = null)
    {
        return Arr::get($this->data, $key, $default);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return static
     */
    protected function setValue($key, $value)
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    /**
     * Write a string as information output.
     *
     * @param string $string
     */
    protected function info($string)
    {
        $this->writeLog($string, 'info');
    }

    /**
     * Write a string as error output.
     *
     * @param string $string
     */
    protected function error($string)
    {
        $this->writeLog($string, 'error');
    }

    /**
     * Write a string as warning output.
     *
     * @param string $string
     */
    protected function warn($string)
    {
        $this->writeLog($string, 'warning');
    }

    /**
     * @param string $message
     * @param string $type
     */
    protected function writeLog($message, $type = 'info')
    {
        $styled = $type ? "<{$type}>[%s][%s] %s</{$type}> %s: %s" : '[%s][%s] %s %s: %s';

        app()->make(ConsoleOutput::class)->writeln(sprintf(
            $styled,
            Carbon::now()->format('Y-m-d H:i:s'),
            $this->job->getJobId(),
            str_pad('Processing:', 11),
            $this->job->resolveName(),
            $message
        ));
    }
}
