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
use Laravel\Lumen\Application as LumenApplication;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @mixin ParameterBag
 */
trait Action
{
    use GetOrSet;
    use Validation;

    /**
     * @var ParameterBag|null
     */
    protected ?ParameterBag $parameterBag = null;

    /**
     * @var Request|null
     */
    protected ?Request $request = null;

    /**
     * action.
     *
     * @return mixed
     */
    abstract public function action(): mixed;

    /**
     * @param  array  $data
     * @param  int  $code
     * @return JsonResponse
     */
    protected function asJson(array $data = [], int $code = 200): JsonResponse
    {
        return response()->json(
            [
                'code' => $code,
                'message' => 'ok',
                'data' => $data
            ]
        );
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
     * Get HTTP Request.
     *
     * @return Request
     * @throws BindingResolutionException
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
     * @throws BindingResolutionException
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
     * @throws ValidationException
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public function invoke(): mixed
    {
        $this->loadParameters();

        return $this->action();
    }

    /**
     * @return mixed
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    public function __invoke(): mixed
    {
        return $this->invoke();
    }

    public function __call($name, $arguments)
    {
        $parameter = $this->getParameter();
        if (method_exists($parameter, $name)) {
            return $parameter->{$name}(...$arguments);
        }

        throw new BadMethodCallException("No such method exists: {$name}");
    }
}
