<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/17
 * Time: 3:51 下午.
 */

namespace HughCube\Laravel\Knight\Exceptions;

use Illuminate\Database\Connection;
use RuntimeException;
use Throwable;

class NotCloseTransactionException extends RuntimeException
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection, $message = null, $code = 0, Throwable $previous = null)
    {
        $this->connection = $connection;
        parent::__construct($message, $code, $previous);
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }
}
