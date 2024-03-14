<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 11:27 上午.
 */

namespace HughCube\Laravel\Knight\Queue;

use BadMethodCallException;
use Exception;
use HughCube\Laravel\Knight\Contracts\Queue\FromFlowJob;
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\Laravel\Knight\Traits\GetOrSet;
use HughCube\Laravel\Knight\Traits\Logger;
use HughCube\Laravel\Knight\Traits\ParameterBag as ParameterBagTrait;
use HughCube\Laravel\Knight\Traits\Validation;
use HughCube\StaticInstanceInterface;
use HughCube\StaticInstanceTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * @method static PendingDispatch|static        dispatch(...$arguments)
 * @method static PendingDispatch|Fluent|static dispatchIf($boolean, ...$arguments)
 * @method static PendingDispatch|Fluent|static dispatchUnless($boolean, ...$arguments)
 */
abstract class Job implements ShouldQueue, StaticInstanceInterface, FromFlowJob
{
    use Logger;
    use GetOrSet;
    use Queueable;
    use Container;
    use Validation;
    use Dispatchable;
    use ParameterBagTrait;
    use InteractsWithQueue;
    use StaticInstanceTrait;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array|string|null
     */
    protected $logChannel = null;

    /**
     * @var string|int|null
     */
    protected $pid = null;

    /**
     * @var null|FlowJobDescribe
     */
    protected $flowJobDescribe = null;

    /**
     * @var Carbon|null
     */
    private $jobStartedAt = null;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function handle(): void
    {
        $this->jobStartedAt = Carbon::now();
        $this->loadParameters();

        try {
            $this->beforeAction();
            $this->action();
        } finally {
            $this->afterAction();
        }
    }

    protected function beforeAction()
    {
    }

    abstract protected function action(): void;

    protected function afterAction()
    {
    }

    protected function getJobStartedAt(): Carbon
    {
        if (!$this->jobStartedAt instanceof Carbon) {
            $this->jobStartedAt = Carbon::now();
        }

        return $this->jobStartedAt;
    }

    protected function getDelays(): float
    {
        return round(Carbon::now()->diffInMicroseconds($this->getJobStartedAt()) / 1000, 2);
    }

    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     *
     * @throws
     *
     * @phpstan-ignore-next-line
     */
    protected function loadParameters()
    {
        if ($this->parameterBag instanceof ParameterBag) {
            return;
        }

        $this->parameterBag = new ParameterBag($this->validate($this->getData()));
    }

    protected function getSerializeData(): string
    {
        return base64_encode(serialize($this->data));
    }

    protected function getJsonData(int $flags = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->data, $flags);
    }

    protected function getValidData(): array
    {
        return $this->p()->all();
    }

    protected function getSerializeValidData(): string
    {
        return base64_encode(serialize($this->getValidData()));
    }

    protected function getJsonValidData(int $flags = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->getValidData(), $flags);
    }

    /**
     * @throws Exception
     */
    protected function getPid(): string
    {
        if (null === $this->pid) {
            $hostname = base_convert(abs(crc32(gethostname())), 10, 36);
            $pid = base_convert(getmypid(), 10, 36);
            $random = base_convert(abs(crc32(random_bytes(10))), 10, 36);
            $this->setPid(sprintf('%s-%s-%s', $hostname, $pid, $random));
        }

        return $this->pid;
    }

    /**
     * @return $this
     */
    public function setPid(string $pid)
    {
        $this->pid = $pid;

        return $this;
    }

    protected function getName(object $job = null): string
    {
        return Str::afterLast(get_class($job ?? $this), '\\');
    }

    /**
     * @return array|string|null
     */
    public function getLogChannel()
    {
        return $this->logChannel;
    }

    /**
     * @param  array|string|null  $channel
     *
     * @return $this
     */
    public function setLogChannel($channel = null)
    {
        $this->logChannel = $channel;

        return $this;
    }

    /**
     * @return void
     * @throws Exception
     *
     */
    public function log($level, string $message, array $context = [])
    {
        $message = sprintf(
            '%s [%s] [%.2fms] %s',
            $this->getName(),
            $this->getPid(),
            $this->getDelays(),
            $message
        );

        Log::channel($this->getLogChannel())->log($level, $message, $context);
    }

    public function setFlowJobDescribe(FlowJobDescribe $describe)
    {
        $this->flowJobDescribe = $describe;
    }

    public function isDelayDeleteFlowJob(): bool
    {
        return false;
    }

    /**
     * @param  string  $key
     * @param  null  $default
     *
     * @return mixed
     *
     * @deprecated Will be removed in a future version.
     */
    protected function get(string $key, $default = null)
    {
        return Arr::get($this->getValidData(), $key, $default);
    }

    /**
     * @param  mixed  $key
     *
     * @return bool
     *
     * @deprecated Will be removed in a future version.
     */
    protected function has($key): bool
    {
        return Arr::has($this->getValidData(), $key);
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return $this
     *
     * @deprecated Will be removed in a future version.
     */
    protected function set(string $key, $value)
    {
        $this->p()->set($key, $value);

        return $this;
    }

    /**
     * @return false|mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->p(), $name)) {
            return call_user_func_array([$this->p(), $name], $arguments);
        }

        throw new BadMethodCallException("No such method exists: {$name}");
    }
}
