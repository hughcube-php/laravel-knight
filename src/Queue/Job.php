<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 11:27 上午.
 */

namespace HughCube\Laravel\Knight\Queue;

use BadMethodCallException;
use Carbon\Carbon;
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
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
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
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use GetOrSet;
    use Validation;
    use Logger;
    use StaticInstanceTrait;
    use ParameterBagTrait;
    use Container;
    use SerializesModels;

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
     * @var Carbon
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
        $this->action();
    }

    abstract protected function action(): void;

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
     * @return array
     */
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

    /**
     * @return string
     */
    protected function getSerializeData(): string
    {
        return base64_encode(serialize($this->data));
    }

    /**
     * @param int $flags
     *
     * @return string
     */
    protected function getJsonData(int $flags = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->data, $flags);
    }

    /**
     * @return array
     */
    protected function getValidData(): array
    {
        return $this->p()->all();
    }

    /**
     * @return string
     */
    protected function getSerializeValidData(): string
    {
        return base64_encode(serialize($this->getValidData()));
    }

    /**
     * @param int $flags
     *
     * @return string
     */
    protected function getJsonValidData(int $flags = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->getValidData(), $flags);
    }

    /**
     * @return string|int
     */
    protected function getPid()
    {
        if (null === $this->pid) {
            $this->setPid(base_convert(abs(crc32(Str::random())), 10, 36));
        }

        return $this->pid;
    }

    /**
     * @param string|int|null $pid
     *
     * @return $this
     */
    public function setPid($pid)
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
     * @param array|string|null $channel
     *
     * @return $this
     */
    public function setLogChannel($channel = null)
    {
        $this->logChannel = $channel;

        return $this;
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, string $message, array $context = [])
    {
        $message = sprintf(
            '[%sms] [%s:%s] [%s:%s] %s',
            $this->getDelays(),
            gethostname(), getmypid(),
            $this->getName(), $this->getPid(),
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
     * @param string $key
     * @param null $default
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
     * @param mixed $key
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
     * @param string $key
     * @param mixed $value
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
     * @param string $name
     * @param array $arguments
     *
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
