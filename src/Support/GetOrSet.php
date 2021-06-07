<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 11:15 上午
 */

namespace HughCube\Laravel\Knight\Support;

trait GetOrSet
{
    /**
     * @var array
     */
    private $classSelfHughCubeKnightCacheStorage = [];

    /**
     * The user builds virtual properties.
     *
     * return $this->getOrSet(__METHOD__, function (){
     *     return Model::findById($this->getParameter()->get('id'));
     * });
     *
     * @param mixed $name
     * @param callable $callable
     * @param bool $reset
     *
     * @return mixed
     */
    protected function getOrSet($name, $callable, $reset = false)
    {
        $key = md5(serialize($name));
        if (!array_key_exists($key, $this->classSelfHughCubeKnightCacheStorage) || $reset) {
            $this->classSelfHughCubeKnightCacheStorage[$key] = $callable();
        }

        return $this->classSelfHughCubeKnightCacheStorage[$key];
    }
}
