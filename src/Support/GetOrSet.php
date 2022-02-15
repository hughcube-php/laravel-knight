<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/7
 * Time: 11:15 上午.
 */

namespace HughCube\Laravel\Knight\Support;

use Illuminate\Support\Facades\Cache;

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
     * @param  string  $key
     * @param  callable  $callable
     * @return mixed
     */
    protected function getOrSet(string $key, callable $callable): mixed
    {
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
