<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/17
 * Time: 17:33.
 */

namespace HughCube\Laravel\Knight\Routing;

use Symfony\Component\HttpFoundation\Response;

/**
 * @mixin Action
 *
 * @deprecated Will be removed in a future version.
 * @see PinCodeSms
 */
trait SendPinCodeSms
{
    use PinCodeSms;

    protected function makeResponse(): Response
    {
        return $this->asResponse();
    }
}
