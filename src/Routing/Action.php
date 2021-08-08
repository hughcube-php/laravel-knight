<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/3/13
 * Time: 11:46 下午.
 */

namespace HughCube\Laravel\Knight\Routing;

use HughCube\Laravel\Knight\Http\LaravelRequest;
use HughCube\Laravel\Knight\Support\GetOrSet;
use HughCube\Laravel\Knight\Support\Validation;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Application as LumenApplication;
use Symfony\Component\HttpFoundation\ParameterBag;

trait Action
{
    use GetOrSet;
    use Validation;

    /**
     * @var ParameterBag|null
     */
    protected $parameterBag = null;

    /**
     * @var Request|null
     */
    protected $request = null;

    /**
     * action.
     *
     * @return mixed
     */
    abstract public function action();

    /**
     * @return ContainerContract
     */
    protected function getContainer(): ContainerContract
    {
        return IlluminateContainer::getInstance();
    }

    /**
     * Get HTTP Request.
     *
     * @return Request|LumenApplication|LaravelRequest
     */
    protected function getRequest(): Request
    {
        if ($this->request instanceof Request) {
            return $this->request;
        }

        if ($this->getContainer() instanceof LumenApplication) {
            return $this->request = $this->getContainer()->make(Request::class);
        }

        return $this->request = $this->getContainer()->make('request');
    }

    /**
     * load parameters.
     * @throws ValidationException
     */
    protected function loadParameters()
    {
        if ($this->parameterBag instanceof ParameterBag) {
            return;
        }

        $validData = $this->validate($this->getRequest()->all());
        $this->parameterBag = new ParameterBag($validData);
    }

    /**
     * @return ParameterBag
     * @throws
     */
    protected function getParameter(): ParameterBag
    {
        $this->loadParameters();

        return $this->parameterBag;
    }

    /**
     * @return mixed
     * @throws ValidationException
     */
    public function invoke()
    {
        $this->loadParameters();

        return $this->action();
    }

    /**
     * @return mixed
     * @throws ValidationException
     */
    public function __invoke()
    {
        return $this->invoke();
    }
}
