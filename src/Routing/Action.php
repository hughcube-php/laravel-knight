<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/3/13
 * Time: 11:46 下午.
 */

namespace HughCube\Laravel\Knight\Routing;

use BadMethodCallException;
use HughCube\Laravel\Knight\Ide\Http\KIdeRequest;
use HughCube\Laravel\Knight\Support\Carbon;
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\Laravel\Knight\Traits\GetOrSet;
use HughCube\Laravel\Knight\Traits\ParameterBag as ParameterBagTrait;
use HughCube\Laravel\Knight\Traits\Validation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

trait Action
{
    use GetOrSet;
    use Validation;
    use ParameterBagTrait;
    use Container;

    /**
     * action.
     *
     * @return mixed
     */
    abstract protected function action();

    /**
     * @return int|string|null
     */
    protected function getAuthId()
    {
        return Auth::id() ?: null;
    }

    /**
     * @return mixed
     */
    protected function getAuthUser()
    {
        /** @var mixed $use */
        $use = Auth::user();

        return $use;
    }

    protected function getActionStartedAt(): Carbon
    {
        /** @var Carbon $dateTime */
        $dateTime = $this->getOrSet(__METHOD__, function () {
            return Carbon::now();
        });

        return $dateTime->clone();
    }

    /**
     * @param array $data
     * @param int   $code
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
     * @param array $data
     * @param int   $code
     *
     * @return Response
     */
    protected function asResponse(array $data = [], int $code = 200): Response
    {
        return new JsonResponse([
            'code'    => $code,
            'message' => 'ok',
            'data'    => $data,
        ]);
    }

    /**
     * @throws
     *
     * @return Request|\Request|KIdeRequest
     * @phpstan-ignore-next-line
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
     * @throws
     *
     * @return mixed
     * @phpstan-ignore-next-line
     */
    public function invoke()
    {
        // Reset the status on each request
        // In Octane, the state of the controller is not reset
        $this->clearActionStatus();

        // Log the time of entry in the action logic
        $this->getActionStartedAt();

        // Collect all validated parameters
        $this->loadParameters();

        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

        // Run all onActioning* methods before action
        foreach ($methods as $method) {
            if (Str::startsWith($method->getName(), 'onActioning')) {
                $method->invoke($this);
            }
        }

        $response = $this->action();

        // Run all onActioned* methods after the action
        foreach ($methods as $method) {
            if (Str::startsWith($method->getName(), 'onActioned')) {
                $method->invoke($this);
            }
        }

        // Clean up the state once the action is complete
        $this->clearActionStatus();

        return $response;
    }

    protected function clearActionStatus()
    {
        $this->parameterBag = null;
        $this->getIHKCStore()->clear();
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
     * @param string $name
     * @param array  $arguments
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
