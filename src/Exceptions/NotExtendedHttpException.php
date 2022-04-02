<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/4/2
 * Time: 15:35.
 */

namespace HughCube\Laravel\Knight\Exceptions;

class NotExtendedHttpException extends \Symfony\Component\HttpKernel\Exception\HttpException
{
    public function __construct(string $message = '', \Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(510, $message, $previous, $headers, $code);
    }
}
