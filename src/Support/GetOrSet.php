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
    private array $hughCubeKnightClassSelfCacheStorage = [];

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
    protected function getOrSet(mixed $name, callable $callable): mixed
    {
        $key = md5(serialize($name));
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
