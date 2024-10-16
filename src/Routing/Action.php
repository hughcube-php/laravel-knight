<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/3/13
 * Time: 11:46 下午.
 */

namespace HughCube\Laravel\Knight\Routing;

use BadMethodCallException;
use HughCube\Laravel\Knight\Events\ActionProcessed;
use HughCube\Laravel\Knight\Events\ActionProcessing;
use HughCube\Laravel\Knight\Http\Request as KnightRequest;
use HughCube\Laravel\Knight\Ide\Http\KIdeRequest;
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\Laravel\Knight\Traits\GetOrSet;
use HughCube\Laravel\Knight\Traits\ParameterBag as ParameterBagTrait;
use HughCube\Laravel\Knight\Traits\Validation;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

trait Action
{
    use GetOrSet;
    use Validation;
    use ParameterBagTrait;
    use Container;

    /**
     * @return mixed
     */
    public function __invoke()
    {
        return $this->invoke();
    }

    /**
     * @throws
     *
     * @return mixed
     *
     * @phpstan-ignore-next-line
     */
    public function invoke()
    {
        // Reset the status on each request
        // In Octane, the state of the controller is not reset
        $this->clearActionStatus();

        // Log the time of entry in the action logic
        $this->getActionStartedAt(true);

        // Collect all validated parameters
        $this->loadParameters();

        $this->getEventsDispatcher()->dispatch(new ActionProcessing($this));

        try {
            $this->beforeAction();
            $result = $this->action();
        } finally {
            $this->afterAction();
        }
        $this->getEventsDispatcher()->dispatch(new ActionProcessed($this));

        return $result;
    }

    protected function clearActionStatus()
    {
        $this->parameterBag = null;
        $this->getIHKCStore()->clear();
    }

    protected function getActionStartedAt($share = false): Carbon
    {
        /** @var Carbon $dateTime */
        $dateTime = $this->getOrSet(__METHOD__, function () {
            return Carbon::now();
        });

        return $share ? $dateTime : $dateTime->clone();
    }

    /**
     * @throws BindingResolutionException
     * @throws ValidationException
     */
    protected function loadParameters(): ParameterBag
    {
        return $this->parameterBag ??= new ParameterBag($this->validate($this->getRequest()));
    }

    /**
     * @throws
     *
     * @return Request|\Request|KIdeRequest|KnightRequest
     *
     * @phpstan-ignore-next-line
     */
    protected function getRequest(): Request
    {
        return $this->getContainer()->make('request');
    }

    protected function beforeAction()
    {
    }

    /**
     * action.
     *
     * @return mixed
     */
    abstract protected function action();

    protected function afterAction()
    {
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
            return $this->p()->{$name}(...$arguments);
        }

        throw new BadMethodCallException("No such method exists: {$name}");
    }

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

    /**
     * @deprecated It's a name change.
     * @see static::asResponse()
     */
    protected function asJson(array $data = [], int $code = 200): JsonResponse
    {
        /** @phpstan-ignore-next-line */
        return $this->asResponse($data, $code);
    }

    protected function asResponse(array $data = [], int $code = 200): Response
    {
        return new JsonResponse([
            'code'    => $code,
            'message' => 'ok',
            'data'    => $data ?: new stdClass(),
        ]);
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
}
