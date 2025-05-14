<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/15
 * Time: 8:42 下午.
 */

namespace HughCube\Laravel\Knight\OPcache\Actions;

use Exception;
use HughCube\Laravel\Knight\OPcache\LoadedOPcacheExtension;
use HughCube\Laravel\Knight\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class ResetAction extends Controller
{
    use LoadedOPcacheExtension;

    /**
     * @throws Exception
     */
    protected function action(): Response
    {
        $this->loadedOPcacheExtension();

        if (!opcache_reset()) {
            throw new Exception('Failed to reset OPcache.');
        }

        return $this->asResponse();
    }
}
