<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/3/13
 * Time: 11:46 下午.
 */

namespace HughCube\Laravel\Knight\Routing;

use BadMethodCallException;
use HughCube\Laravel\Knight\Support\GetOrSet;
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Support\ParameterBagBak;
use HughCube\Laravel\Knight\Support\Validation;
use Illuminate\Config\Repository;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * @mixin ParameterBagBak
 */
trait Action
{
    use GetOrSet;
    use Validation;

    /**
     * @var ParameterBag|null
     */
    private $parameterBag = null;

    /**
     * action.
     *
     * @return mixed
     */
    abstract protected function action();

    /**
     * @param array $data
     * @param int   $code
     *
     * @return JsonResponse
     */
    protected function asJson(array $data = [], int $code = 200): JsonResponse
    {
        return new JsonResponse([
            'code'    => $code,
            'message' => 'ok',
            'data'    => $data,
        ]);
    }

    /**
     * @return IlluminateContainer
     */
    protected function getContainer(): IlluminateContainer
    {
        return IlluminateContainer::getInstance();
    }

    /**
     * @throws BindingResolutionException
     *
     * @return Repository
     */
    protected function getContainerConfig(): Repository
    {
        return $this->getContainer()->make('config');
    }

    /**
     * @throws BindingResolutionException
     *
     * @return Request
     */
    protected function getRequest(): Request
    {
        return $this->getContainer()->make('request');
    }

    /**
     * @throws ValidationException
     * @throws BindingResolutionException
     *
     * @return void
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
     * @throws ValidationException
     * @throws BindingResolutionException
     *
     * @return ParameterBag
     *
     * @deprecated Will be removed in a future version.
     */
    protected function getParameter(): ParameterBag
    {
        return $this->p();
    }

    /**
     * @throws ValidationException
     * @throws BindingResolutionException
     *
     * @return ParameterBag
     */
    protected function p(): ParameterBag
    {
        $this->loadParameters();

        return $this->parameterBag;
    }

    /**
     * @throws BindingResolutionException
     * @throws ValidationException
     *
     * @return mixed
     */
    public function invoke()
    {
        // Reset the status on each request
        $this->parameterBag = null;
        $this->flushHughCubeKnightClassSelfCacheStorage();

        $this->loadParameters();

        return $this->action();
    }

    /**
     * @throws BindingResolutionException
     * @throws ValidationException
     *
     * @return mixed
     */
    public function __invoke()
    {
        return $this->invoke();
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @throws ValidationException
     * @throws BindingResolutionException
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->p(), $name)) {
            return call_user_func_array([$this->p(), $name], $arguments);
        }

        throw new BadMethodCallException("No such method exists: {$name}");
    }
}
