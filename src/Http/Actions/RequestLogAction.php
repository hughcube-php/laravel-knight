<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36.
 */

namespace HughCube\Laravel\Knight\Http\Actions;

use HughCube\Laravel\Knight\Routing\Controller;
use Illuminate\Support\Facades\Log;

class RequestLogAction extends Controller
{
    /**
     * @return string
     */
    protected function action(): string
    {
        Log::info(sprintf(
            'request: uri:%s, headers:%s, body:%s',
            $this->getRequest()->getUri(),
            json_encode($this->getRequest()->headers->all()),
            serialize($this->getRequest()->getContent())
        ));

        return 'success';
    }
}
