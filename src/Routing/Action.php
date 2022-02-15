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
use HughCube\Laravel\Knight\Support\Validation;
use Illuminate\Config\Repository;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @mixin ParameterBag
 */
trait Action
{
    use GetOrSet;
    use Validation;

    private ?ParameterBag $parameterBag = null;

    /**
     * action.
     *
     * @return mixed
     */
    abstract protected function action(): mixed;

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
     */
    protected function getParameter(): ParameterBag
    {
        $this->loadParameters();

        return $this->parameterBag;
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     * @throws ValidationException
     * @throws PhpVersionNotSupportedException
     */
    public function invoke(): mixed
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
     * @throws PhpVersionNotSupportedException
     * @throws ValidationException
     */
    public function __invoke(): mixed
    {
        return $this->invoke();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    public function __call($name, $arguments)
    {
        $parameter = $this->getParameter();
        if (method_exists($parameter, $name)) {
            return $parameter->{$name}(...$arguments);
        }

        throw new BadMethodCallException("No such method exists: {$name}");
    }
}
