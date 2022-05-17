<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/5/17
 * Time: 21:16
 */

namespace HughCube\Laravel\Knight\Exceptions;

interface DataExceptionInterface
{
    /**
     * Get the array.
     */
    public function getData(): array;
}
