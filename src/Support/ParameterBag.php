<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 22:21.
 */

namespace HughCube\Laravel\Knight\Support;

use ArrayIterator;

class ParameterBag
{
    /**
     * Parameter storage.
     */
    protected $parameters = [];

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function replace(array $parameters = []): ParameterBag
    {
        $this->parameters = $parameters;

        return $this;
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function keys(): array
    {
        return array_keys($this->parameters);
    }

    /**
     * @param string|int $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * @param string|int $key
     * @param mixed      $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->parameters[$key] : $default;
    }

    /**
     * @param string|int $key
     * @param mixed      $value
     *
     * @return $this
     */
    public function set($key, $value): ParameterBag
    {
        $this->parameters[$key] = $value;

        return $this;
    }

    /**
     * @param string|int $key
     * @param mixed      $value
     *
     * @return $this
     */
    public function add($key, $value): ParameterBag
    {
        if ($this->has($key)) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * @param string|int $key
     *
     * @return $this
     */
    public function remove($key): ParameterBag
    {
        if ($this->has($key)) {
            unset($this->parameters[$key]);
        }

        return $this;
    }

    /**
     * @param string|int $key
     * @param mixed      $default
     *
     * @return bool
     */
    public function getBoolean($key, $default = false): bool
    {
        return true === filter_var($this->get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param string|int $key
     * @param mixed      $default
     *
     * @return int
     */
    public function getInt($key, $default = 0): int
    {
        return intval($this->get($key, $default));
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
