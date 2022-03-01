<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/2/28
 * Time: 17:18.
 */

namespace HughCube\Laravel\Knight\Routing;

use HughCube\Laravel\Knight\Routing\Action as BaseAction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller extends \Illuminate\Routing\Controller
{
    use BaseAction;
    use DispatchesJobs;
    use AuthorizesRequests;

    protected function action()
    {
        throw new NotFoundHttpException();
    }
}
