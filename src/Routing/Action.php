<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/3/13
 * Time: 11:46 下午.
 */

namespace HughCube\Laravel\Knight\Routing;

use HughCube\Laravel\Knight\Http\LaravelRequest;
use HughCube\Laravel\Knight\Http\LumenRequest;
use HughCube\Laravel\Knight\Http\ParameterBag;
use HughCube\Laravel\Knight\Support\GetOrSet;
use HughCube\Laravel\Knight\Support\Validation;
use Illuminate\Contracts\Foundation\Application as LaravelApplication;
use Illuminate\Http\Request;
use Laravel\Lumen\Application as LumenApplication;

trait Action
{
    use GetOrSet;
    use Validation;

    /**
     * @var LaravelRequest|LumenRequest
     */
    private $request;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    /**
     * action.
     *
     * @return mixed
     */
    abstract protected function action();

    /**
     * Get HTTP Request.
     *
     * @return LaravelRequest|LumenRequest
     */
    protected function getRequest()
    {
        if ($this->request instanceof Request) {
            return $this->request;
        }

        /** @var LumenApplication|LaravelApplication $app */
        $app = app();

        if ($app instanceof LumenApplication) {
            return $this->request = $app->make(Request::class);
        }

        return $this->request = $app->make('request');
    }

    /**
     * Get request validated results.
     *
     * @return ParameterBag
     */
    protected function parameter($key = null)
    {
        if (null === $key) {
            return $this->parameterBag;
        }

        return $this->parameterBag->get($key);
    }

    /**
     * load parameters.
     */
    protected function loadParameters()
    {
        if (!$this->parameterBag instanceof ParameterBag) {
            $this->parameterBag = new ParameterBag($this->validate($this->getRequest()->all()));
        }
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        $this->loadParameters();

        return $this->action();
    }
}
