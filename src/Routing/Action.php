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
use HughCube\Laravel\Knight\Support\Validation;
use Illuminate\Config\Repository;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Psr\SimpleCache\InvalidArgumentException;

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
     * @param  array  $data
     * @param  int  $code
     *
     * @return JsonResponse
     */
    protected function asJson(array $data = [], int $code = 200): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => 'ok',
            'data' => $data,
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
     * @return Repository
     * @throws BindingResolutionException
     */
    protected function getContainerConfig(): Repository
    {
        return $this->getContainer()->make('config');
    }

    /**
     * @return Request
     * @throws BindingResolutionException
     */
    protected function getRequest(): Request
    {
        return $this->getContainer()->make('request');
    }

    /**
     * @return void
     * @throws BindingResolutionException
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
     * @throws BindingResolutionException
     * @throws ValidationException
     * @deprecated Will be removed in a future version.
     */
    protected function getParameter(): ParameterBag
    {
        return $this->p();
    }

    /**
     * @return ParameterBag
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    protected function p(): ParameterBag
    {
        $this->loadParameters();
        return $this->parameterBag;
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    public function invoke()
    {
        # Reset the status on each request
        $this->parameterBag = null;
        $this->flushHughCubeKnightClassSelfCacheStorage();

        $this->loadParameters();

        return $this->action();
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    public function __invoke()
    {
        return $this->invoke();
    }

    /**
     * @param  string  $name
     * @param  array  $arguments
     * @return mixed
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->p(), $name)) {
            return call_user_func_array([$this->p(), $name], $arguments);
        }

        throw new BadMethodCallException("No such method exists: {$name}");
    }
}
