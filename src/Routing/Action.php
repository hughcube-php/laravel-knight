<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/3/13
 * Time: 11:46 下午.
 */

namespace HughCube\Laravel\Knight\Routing;

use BadMethodCallException;
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

    private ?float $_HughCubeActionStartedTimestamp = null;

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
        $this->_HughCubeActionStartedTimestamp = microtime(true);

        // Collect all validated parameters
        $this->loadParameters();

        $this->dispatchActionProcessingEvent();

        try {
            $this->beforeAction();
            $result = $this->action();
        } finally {
            $this->afterAction();
        }
        $this->dispatchActionProcessedEvent();

        return $result;
    }

    protected function dispatchActionProcessingEvent()
    {
        //$this->getEventsDispatcher()->dispatch(new \HughCube\Laravel\Knight\Events\ActionProcessing($this));
    }

    protected function dispatchActionProcessedEvent()
    {
        $this->getEventsDispatcher()->dispatch(new \HughCube\Laravel\Knight\Events\ActionProcessed($this));
    }

    protected function clearActionStatus()
    {
        $this->parameterBag = $this->_HughCubeActionStartedTimestamp = null;
        $this->flushHughCubeKnightClassSelfCacheStorage();
    }

    /**
     * @return Carbon
     */
    protected function getActionStartedAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->_HughCubeActionStartedTimestamp);
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

    /**
     * @deprecated It's a name change.
     * @see static::asSuccess()
     */
    protected function asResponse(array $data = [], int $code = 200): Response
    {
        return new JsonResponse([
            'code'    => $code,
            'message' => 'success',
            'data'    => $data ?: new stdClass(),
        ]);
    }

    protected function asSuccess(array $data = [], int $statusCode = 200): Response
    {
        return new JsonResponse([
            'Code' => 'Success',
            'Message' => 'Success',
            'Data' => $data ?: new stdClass()
        ], $statusCode);
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
