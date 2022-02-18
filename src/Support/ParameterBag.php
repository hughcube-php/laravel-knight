<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 22:21
 */

namespace HughCube\Laravel\Knight\Support;

use ArrayIterator;
use JetBrains\PhpStorm\Pure;

class ParameterBag
{
    /**
     * Parameter storage.
     */
    protected array $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function replace(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    public function has(string|int $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    #[Pure]
    public function get(string|int $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $this->parameters[$key] : $default;
    }

    public function set(string|int $key, mixed $value)
    {
        $this->parameters[$key] = $value;
    }

    public function add(string|int $key, mixed $value)
    {
        if ($this->has($key)) {
            $this->set($key, $value);
        }
    }

    public function remove(string|int $key)
    {
        unset($this->parameters[$key]);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }

    public function count(): int
    {
        return count($this->parameters);
    }
}
