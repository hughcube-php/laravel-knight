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
use Symfony\Component\HttpFoundation\Response;

class ShowRequestAction
{
    use Action;

    /**
     * @return Response
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function action(): Response
    {
        return $this->asJson([
            'uri' => $this->getRequest()->getUri(),
            'https' => $this->getRequest()->isSecure(),
            'method' => $this->getRequest()->getMethod(),
            'host' => $this->getRequest()->getHost(),
            'headers' => $this->getRequest()->headers->all(),
            'server' => $this->getRequest()->server->all(),
            'content' => serialize($this->getRequest()->getContent()),
            '$_SERVER' => $_SERVER,
        ]);
    }
}
