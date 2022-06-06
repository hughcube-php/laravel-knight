<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/6
 * Time: 18:57
 */

namespace HughCube\Laravel\Knight\Mixin\Support;

use Closure;
use Illuminate\Support\Collection;

/**
 * @mixin Collection
 */
class CollectionMixin
{
    public function hasByCallable(): Closure
    {
        return function (callable $key) {
            foreach ($this->getIterator() as $index => $item) {
                if (true === $key($item, $index)) {
                    return true;
                }
            }
            return false;
        };
    }

    public function isIndexed(): Closure
    {
        return function (bool $consecutive = true) {
            if ($this->isEmpty()) {
                return true;
            }

            if ($consecutive) {
                return $this->keys()->toArray() === range(0, $this->count() - 1);
            }

            foreach ($this->getIterator() as $index => $value) {
                if (!is_int($index)) {
                    return false;
                }
            }
            return false;
        };
    }
}
