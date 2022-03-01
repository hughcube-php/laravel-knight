<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/17
 * Time: 3:51 下午.
 */

namespace HughCube\Laravel\Knight\Exceptions;

use Throwable;

class ExceptionWithData extends \Exception
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
