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

abstract class Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use GetOrSet, Validation;

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @var array
     */
    protected array $validData = [];

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
    protected function getData(): array
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
     * @param  string  $message
     */
    protected function info(string $message)
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
