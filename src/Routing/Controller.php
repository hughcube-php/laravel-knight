<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/28
 * Time: 17:18.
 */

namespace HughCube\Laravel\Knight\Routing;

use HughCube\Laravel\Knight\Exceptions\NotExtendedHttpException;
use HughCube\Laravel\Knight\Routing\Action as BaseAction;

class Controller
{
    use BaseAction;

    protected function action()
    {
        throw new NotExtendedHttpException(
            'Further extensions to the request are required for the server to fulfill it.'
        );
    }
}
