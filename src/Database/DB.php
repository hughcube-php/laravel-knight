<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/11/3
 * Time: 13:33.
 */

namespace HughCube\Laravel\Knight\Database;

use Illuminate\Database\QueryException;
use Throwable;

class DB extends \Illuminate\Support\Facades\DB
{
    /**
     * @throws Throwable
     */
    public static function retryOnQueryException(callable $callable, $count = 3, $usleepMicroseconds = null)
    {
        $results = null;
        $exception = null;

        for ($i = 1; $i <= $count; $i++) {
            $exception = null;

            try {
                $results = $callable();
                break;
            } catch (QueryException $exception) {
                if (0 < ($microseconds = $usleepMicroseconds ?? random_int(100000, 1000000))) {
                    usleep($microseconds);
                }
            }
        }

        if ($exception instanceof Throwable) {
            throw $exception;
        }

        return $results;
    }
}
