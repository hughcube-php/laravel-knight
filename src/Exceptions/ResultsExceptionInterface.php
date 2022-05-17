<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/5/17
 * Time: 21:16.
 */

namespace HughCube\Laravel\Knight\Exceptions;

interface ResultsExceptionInterface
{
    /**
     * Get the underlying response instance.
     */
    public function getResults();
}
