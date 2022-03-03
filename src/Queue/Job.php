<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 11:27 上午.
 */

namespace HughCube\Laravel\Knight\Queue;

use BadMethodCallException;
use HughCube\Base\Base;
use HughCube\Laravel\Knight\Support\GetOrSet;
use HughCube\Laravel\Knight\Support\LoggerTrait;
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Support\ParameterBagTrait;
use HughCube\Laravel\Knight\Support\Validation;
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
use Illuminate\Validation\ValidationException;

/**
 * @method static PendingDispatch|static dispatch(...$arguments)
 * @method static PendingDispatch|Fluent|static dispatchIf($boolean, ...$arguments)
 * @method static PendingDispatch|Fluent|static dispatchUnless($boolean, ...$arguments)
 */
abstract class Job implements ShouldQueue, StaticInstanceInterface
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use GetOrSet;
    use Validation;
    use LoggerTrait;
    use StaticInstanceTrait;
    use ParameterBagTrait;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array|string|null
     */
    protected $logChannel = null;

    /**
     * @var int|null
     */
    protected $pid = null;

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
        try {
            $this->loadParameters();
        } catch (ValidationException $exception) {
            $errors = json_encode($exception->errors(), JSON_UNESCAPED_UNICODE);
            $this->info(sprintf('data validation error, errors: %s, data: %s', $errors, $this->getSerializeData()));

            return;
        }

        $this->action();
    }

    abstract protected function action(): void;

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
     * @return int
     */
    protected function getPid()
    {
        if (null === $this->pid) {
            $this->setPid(Base::conv(
                crc32(Str::random(5)),
                '0123456789',
                'LaJhMxlTNSw813CnG2bduYAPrBpZVv0tiykIgUoz5KW6HQDej49csq7fmOXREF'
            ));
        }

        return $this->pid;
    }

    /**
     * @param string|int|null $pid
     *
     * @return $this
     */
    public function setPid($pid): Job
    {
        $this->pid = $pid;

        return $this;
    }

    protected function getName($job = null): string
    {
        return Str::afterLast(get_class(($job ?? $this)), '\\');
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
    public function setLogChannel($channel = null): Job
    {
        $this->logChannel = $channel;

        return $this;
    }

    /**
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, string $message, array $context = [])
    {
        $message = sprintf('[%s-%s] %s', $this->getName(), $this->getPid(), $message);
        Log::channel($this->getLogChannel())->log($level, $message, $context);
    }

    /**
     * @param string $key
     * @param null   $default
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
     * @param mixed  $value
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
     * @param array  $arguments
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
