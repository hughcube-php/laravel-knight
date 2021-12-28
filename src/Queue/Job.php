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
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerTrait;
use Stringable;

/**
 * @method static PendingDispatch|static dispatch(...$arguments)
 * @method static PendingDispatch|Fluent|static dispatchIf($boolean, ...$arguments)
 * @method static PendingDispatch|Fluent|static dispatchUnless($boolean, ...$arguments)
 */
abstract class Job implements ShouldQueue, StaticInstanceInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use GetOrSet, Validation;
    use LoggerTrait;
    use StaticInstanceTrait;

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var array
     */
    protected array $validData = [];

    /**
     * @var array|string|null
     */
    protected null|string|array $logChannel = null;

    /**
     * @var string|int|null
     */
    protected null|string|int $pid = null;

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
            $this->validData = $this->validate($this->getData());
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
     * @return string
     */
    protected function getSerializeData(): string
    {
        return base64_encode(serialize($this->data));
    }

    /**
     * @param  int  $flags
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
        return $this->validData;
    }

    /**
     * @return string
     */
    protected function getSerializeValidData(): string
    {
        return base64_encode(serialize($this->validData));
    }

    /**
     * @param  int  $flags
     * @return string
     */
    protected function getJsonValidData(int $flags = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->validData, $flags);
    }

    /**
     * @param  string  $key
     * @param  null  $default
     *
     * @return mixed
     */
    protected function get(string $key, $default = null): mixed
    {
        return Arr::get($this->validData, $key, $default);
    }

    /**
     * @param  mixed  $key
     * @return bool
     */
    protected function has(mixed $key): bool
    {
        return Arr::has($this->validData, $key);
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     *
     * @return static
     */
    protected function set(string $key, mixed $value): static
    {
        Arr::set($this->validData, $key, $value);

        return $this;
    }

    /**
     * @return string|int
     */
    protected function getPid(): string|int
    {
        if (null === $this->pid) {
            $this->setPid(Str::random(5));
        }

        return $this->pid;
    }

    /**
     * @param  string|int|null  $pid
     * @return $this
     */
    public function setPid(null|string|int $pid): static
    {
        $this->pid = $pid;
        return $this;
    }

    #[Pure]
    protected function getName($job = null): string
    {
        return Str::afterLast(get_class(($job ?? $this)), '\\');
    }

    protected function getLogChannel(): array|string|null
    {
        return $this->logChannel;
    }

    public function setLogChannel(array|string|null $channel = null): static
    {
        $this->logChannel = $channel;
        return $this;
    }

    /**
     * @param $level
     * @param  string|Stringable  $message
     * @param  array  $context
     * @return void
     */
    protected function log($level, string|Stringable $message, array $context = [])
    {
        $message = sprintf('[%s-%s] %s', $this->getName(), $this->getPid(), $message);
        Log::channel($this->getLogChannel())->log($level, $message, $context);
    }
}
