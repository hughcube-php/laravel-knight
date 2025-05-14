<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/2/18
 * Time: 14:33.
 */

namespace HughCube\Laravel\Knight\Support;

use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

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

    public static function convertExceptionToArray(Throwable $e): array
    {
        $array = [
            'Code'       => $e->getCode(),
            'Exception'  => get_class($e),
            'Message'    => $e->getMessage(),
            'File'       => sprintf('%s(%s)', $e->getFile(), $e->getLine()),
            'StackTrace' => $e->getTrace(),
        ];

        if ($e instanceof ValidationException) {
            $array['Errors'] = $e->errors();
        }

        if (($prev = $e->getPrevious()) !== null) {
            $array['Previous'] = static::convertExceptionToArray($prev);
        }

        return $array;
    }
}
