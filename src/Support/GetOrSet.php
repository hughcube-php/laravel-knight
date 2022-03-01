<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 11:15 上午.
 */

namespace HughCube\Laravel\Knight\Support;

trait GetOrSet
{
    /**
     * @var array
     */
    private $hughCubeKnightClassSelfCacheStorage = [];

    /**
     * The user builds virtual properties.
     *
     * return $this->getOrSet(__METHOD__, function (){
     *     return Model::findById($this->getParameter()->get('id'));
     * });
     *
     * @param  mixed  $name
     * @param  callable  $callable
     *
     * @return mixed
     */
    protected function getOrSet($name, callable $callable)
    {
        $key = sprintf('%s:%s', md5($key = serialize($name)), crc32($key));
        if (!array_key_exists($key, $this->hughCubeKnightClassSelfCacheStorage)) {
            $this->hughCubeKnightClassSelfCacheStorage[$key] = $callable();
        }

        return $this->hughCubeKnightClassSelfCacheStorage[$key];
    }

    /**
     * @return void
     */
    public function flushHughCubeKnightClassSelfCacheStorage()
    {
        $this->hughCubeKnightClassSelfCacheStorage = [];
    }
}
