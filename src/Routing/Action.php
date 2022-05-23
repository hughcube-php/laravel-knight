<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/3/13
 * Time: 11:46 下午.
 */

namespace HughCube\Laravel\Knight\Routing;

use BadMethodCallException;
use HughCube\Laravel\Knight\Http\LaravelRequest;
use HughCube\Laravel\Knight\Support\Carbon;
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\Laravel\Knight\Traits\GetOrSet;
use HughCube\Laravel\Knight\Traits\ParameterBag as ParameterBagTrait;
use HughCube\Laravel\Knight\Traits\Validation;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

trait Action
{
    use GetOrSet;
    use Validation;
    use ParameterBagTrait;
    use Container;

    /**
     * @return $this
     * @throws ReflectionException
     */
    protected function initializers()
    {
        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

        foreach ($methods as $method) {
            if (!Str::startsWith($method->getName(), 'initialize')) {
                continue;
            }

            if ($method->getName() === __FUNCTION__) {
                continue;
            }

            $method->invoke($this);
        }

        return $this;
    }

    /**
     * action.
     *
     * @return mixed
     */
    abstract protected function action();

    /**
     * @param  bool  $must
     *
     * @return int|string|null
     * @throws AuthenticationException
     *
     */
    protected function getAuthId(bool $must = true)
    {
        $id = Auth::id() ?: null;
        if ($must && empty($id)) {
            throw new AuthenticationException();
        }

        return $id;
    }

    /**
     * @param  bool  $must
     *
     * @return Authenticatable|null
     * @throws AuthenticationException
     *
     */
    protected function getAuthUser(bool $must = true): ?Authenticatable
    {
        $user = Auth::user();
        if ($must && !$user instanceof Authenticatable) {
            throw new AuthenticationException();
        }

        return $user instanceof Authenticatable ? $user : null;
    }

    protected function getActionStartDateTime(): Carbon
    {
        /** @var Carbon $dateTime */
        $dateTime = $this->getOrSet(__METHOD__, function () {
            return Carbon::now();
        });

        return $dateTime->clone();
    }

    /**
     * @param  array  $data
     * @param  int  $code
     *
     * @return JsonResponse
     *
     * @deprecated It's a name change
     */
    protected function asJson(array $data = [], int $code = 200): JsonResponse
    {
        /** @phpstan-ignore-next-line */
        return $this->asResponse($data, $code);
    }

    /**
     * @param  array  $data
     * @param  int  $code
     *
     * @return Response
     */
    protected function asResponse(array $data = [], int $code = 200): Response
    {
        return new JsonResponse([
            'code' => $code,
            'message' => 'ok',
            'data' => $data,
        ]);
    }

    /**
     * @return Request|LaravelRequest
     * @phpstan-ignore-next-line
     * @throws
     *
     */
    protected function getRequest(): Request
    {
        return $this->getContainer()->make('request');
    }

    /**
     * @inheritDoc
     *
     * @throws
     * @phpstan-ignore-next-line
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
     *
     * @deprecated Will be removed in a future version.
     */
    protected function getParameter(): ParameterBag
    {
        return $this->p();
    }

    /**
     * @return mixed
     * @phpstan-ignore-next-line
     * @throws
     *
     */
    public function invoke()
    {
        // Reset the status on each request
        // In Octane, the state of the controller is not reset
        $this->parameterBag = null;
        $this->getIHKCStore()->clear();

        // Log the time of entry in the action logic
        $this->getActionStartDateTime();

        $this->loadParameters();

        return $this->initializers()->action();
    }

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return $this->invoke();
    }

    public function __get(string $name)
    {
        return $this->p()->get($name);
    }

    /**
     * @param  string  $name
     * @param  array  $arguments
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
