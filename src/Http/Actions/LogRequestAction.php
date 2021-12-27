<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36
 */

namespace HughCube\Laravel\Knight\Http\Actions;

use Exception;
use HughCube\Laravel\Knight\Routing\Action;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequestAction
{
    use Action;

    /**
     * @return Response
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function action(): string
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