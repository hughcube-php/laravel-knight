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

    public function isEmpty($key): bool
    {
        return empty($this->get($key));
    }

    public function isNull($key): bool
    {
        return null === $this->get($key);
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
     * @param callable   $callable
     *
     * @return false|mixed
     */
    public function whenHas($key, callable $callable)
    {
        if (!$this->has($key)) {
            return false;
        }

        return $callable($this->get($key), $key);
    }

    /**
     * @param string|int $key
     * @param callable   $callable
     *
     * @return false|mixed
     */
    public function whenNotNull($key, callable $callable)
    {
        if ($this->isNull($key)) {
            return false;
        }

        return $callable($this->get($key), $key);
    }

    /**
     * @param string|int $key
     * @param callable   $callable
     *
     * @return false|mixed
     */
    public function whenNotEmpty($key, callable $callable)
    {
        if ($this->isEmpty($key)) {
            return false;
        }

        return $callable($this->get($key), $key);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->parameters);
    }

    public function count(): int
    {
        return count($this->parameters);
    }

    /**
     * @param string|int $key
     * @param mixed      $default
     *
     * @return null|bool
     */
    public function getBoolean($key, $default = false): ?bool
    {
        $value = $this->get($key);

        if (in_array($value, [1, '1', true, 'true', 'on', 'yes'], true)) {
            return true;
        }

        if (in_array($value, [0, '0', false, 'false', 'off', 'no'], true)) {
            return false;
        }

        return $default;
    }

    /**
     * @param string|int $key
     * @param mixed      $default
     *
     * @return null|int
     */
    public function getInt($key, $default = 0): ?int
    {
        $value = $this->get($key);

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value) && ctype_digit(strval($value))) {
            return intval($value);
        }

        return $default;
    }

    /**
     * @param string|int $key
     * @param mixed      $default
     *
     * @return null|float
     */
    public function getFloat($key, $default = 0): ?float
    {
        $value = $this->get($key);

        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return floatval($value);
        }

        return $default;
    }

    /**
     * 获取一个由数字和字母组成的参数.
     *
     * @param string|int $key
     * @param string     $default
     *
     * @return null|string
     */
    public function getAlpha($key, string $default = ''): ?string
    {
        $value = $this->get($key);

        return ctype_alpha($value) ? $value : $default;
    }

    /**
     * 获取一个由数字和字母组成的参数.
     *
     * @param string|int $key
     * @param string     $default
     *
     * @return null|string
     */
    public function getAlnum($key, string $default = ''): ?string
    {
        $value = $this->get($key);

        return ctype_alnum($value) ? $value : $default;
    }

    /**
     * @param string|int $key
     * @param string     $default
     *
     * @return null|string
     */
    public function getDigits($key, string $default = '0'): ?string
    {
        $value = $this->get($key);
        if (is_numeric($value) && ctype_digit(strval($value))) {
            return strval($value);
        }

        return $default;
    }
}
