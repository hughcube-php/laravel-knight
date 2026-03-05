<?php

namespace HughCube\Laravel\Knight\Exceptions;

use Throwable;

class UserCodeException extends UserException
{
    /**
     * @var string
     */
    protected $stringCode;

    /**
     * @param string $code
     * @param string $message
     * @param Throwable|null $previous
     */
    public function __construct($code, $message = '', $previous = null)
    {
        $this->stringCode = $code;

        parent::__construct($message, 0, $previous);
    }

    /**
     * @return string
     */
    public function getStringCode(): string
    {
        return $this->stringCode ?: 'UserException';
    }
}
