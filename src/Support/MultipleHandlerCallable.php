<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/30
 * Time: 16:40.
 */

namespace HughCube\Laravel\Knight\Support;

class MultipleHandlerCallable
{
    /**
     * @var integer
     */
    public $sort;

    /**
     * @var callable|array|string
     */
    public $callable;

    public function __construct($sort, $callable)
    {
        $this->sort = $sort;
        $this->callable = $callable;
    }
}
