<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 4:19 下午.
 */

namespace HughCube\Laravel\Package;

use Illuminate\Support\Arr;

class Manager
{
    /**
     * The alifc server configurations.
     *
     * @var array
     */
    protected $config;

    /**
     * The clients.
     *
     * @var Store[]
     */
    protected $stores = [];

    /**
     * Manager constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get a store by name.
     *
     * @param string|null $name
     *
     * @return Store
     */
    public function store($name = null)
    {
        $name = null == $name ? $this->getDefaultClient() : $name;

        if (isset($this->stores[$name])) {
            return $this->stores[$name];
        }

        return $this->stores[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given store by name.
     *
     * @param string|null $name
     *
     * @return Store
     */
    protected function resolve($name = null)
    {
        return new Store();
    }

    /**
     * Get the default store name.
     *
     * @return string
     */
    public function getDefaultClient()
    {
        return Arr::get($this->config, 'default', 'default');
    }

    /**
     * Get the configuration for a store.
     *
     * @param string $name
     *
     * @return array
     * @throws \InvalidArgumentException
     *
     */
    protected function configuration($name = null)
    {
        $name = $name ?: $this->getDefaultClient();
        $stores = Arr::get($this->config, 'stores', []);
        $defaults = Arr::get($this->config, 'defaults', []);

        if (is_null($store = Arr::get($stores, $name))) {
            throw new \InvalidArgumentException("captcha store [{$name}] not configured.");
        }

        return array_merge($store, $defaults);
    }
}
