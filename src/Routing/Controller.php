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
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;

class Controller extends \Illuminate\Routing\Controller
{
    use BaseAction;
    use DispatchesJobs;
    use AuthorizesRequests;

    protected function action()
    {
        throw new NotExtendedHttpException(
            'Further extensions to the request are required for the server to fulfill it.'
        );
    }
}
