<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/2/18
 * Time: 14:33.
 */

namespace HughCube\Laravel\Knight\Support;

use RuntimeException;

class Helper
{
    /**
     * @throws RuntimeException
     */
    public function assertClassExists(string $class)
    {
        if (!class_exists($class)) {
            throw new RuntimeException(sprintf("Class '%s' does not exist.", $class));
        }
    }

    /**
     * @throws RuntimeException
     */
    public function assertLoadedExtension(string $name)
    {
        if (!extension_loaded($name)) {
            throw new RuntimeException(sprintf('Not have the %s extension loaded.', $name));
        }
    }
}
