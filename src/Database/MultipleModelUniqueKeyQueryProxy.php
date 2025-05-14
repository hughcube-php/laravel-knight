<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/9/3
 * Time: 10:10.
 */

namespace HughCube\Laravel\Knight\Database;

class MultipleModelUniqueKeyQueryProxy
{
    protected array $uniqueKeys = [];

    public function addUniqueKeys($model, $keys): MultipleModelUniqueKeyQueryProxy
    {
        $this->uniqueKeys[$model]['keys'] ??= [];
        $this->uniqueKeys[$model]['keys'] = array_merge($this->uniqueKeys[$model]['keys'], $keys);

        return $this;
    }

    public function query()
    {
    }
}
