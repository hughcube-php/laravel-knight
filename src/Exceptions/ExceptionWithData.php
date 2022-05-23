<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/17
 * Time: 3:51 下午.
 */

namespace HughCube\Laravel\Knight\Exceptions;

use HughCube\Laravel\Knight\Exceptions\Contracts\DataExceptionInterface;
use Throwable;

class ExceptionWithData extends Exception implements DataExceptionInterface
{
    /**
     * @var mixed|null
     */
    protected $data = null;

    public function __construct($data, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->data = $data;
        parent::__construct($message, $code, $previous);
    }

    public function getData(): array
    {
        return $this->data ?: [];
    }
}
