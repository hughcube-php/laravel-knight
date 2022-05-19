<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 11:15 上午.
 */

namespace HughCube\Laravel\Knight\Traits;

use HughCube\Laravel\Knight\Cache\HKStore;

trait GetOrSet
{
    /**
     * @var null|HKStore
     */
    private $IHKCStore = null;

    protected function getIHKCStore(): HKStore
    {
        if (!$this->IHKCStore instanceof HKStore) {
            $this->IHKCStore = new HKStore();
        }

        return $this->IHKCStore;
    }

    /**
     * The user builds virtual properties.
     *
     * return $this->getOrSet(__METHOD__, function (){
     *     return Model::findById($this->getParameter()->get('id'));
     * });
     *
     * @param mixed    $name
     * @param callable $callable
     *
     * @return mixed
     */
    protected function getOrSet($name, callable $callable)
    {
        $cacheKey = sprintf('%s:%s', md5($scalarName = serialize($name)), crc32($scalarName));

        return $this->getIHKCStore()->getOrSet($cacheKey, $callable);
    }

    /**
     * @return void
     *
     * @deprecated
     */
    public function flushHughCubeKnightClassSelfCacheStorage()
    {
        $this->getIHKCStore()->clear();
    }
}
