<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/2/18
 * Time: 14:33.
 */

namespace HughCube\Laravel\Knight\Support;

use Exception;

class Helper
{
    /**
     * @throws Exception
     */
    public function assertClassExists(string $class)
    {
        if (!class_exists($class)) {
            throw new Exception(sprintf("Class '%s' does not exist.", $class));
        }
    }
}
