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
use HughCube\Laravel\Knight\Support\ParameterBag;
use HughCube\Laravel\Knight\Traits\Container;
use HughCube\Laravel\Knight\Traits\GetOrSet;
use HughCube\Laravel\Knight\Traits\ParameterBag as ParameterBagTrait;
use HughCube\Laravel\Knight\Traits\Validation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
     * @return mixed
     *
     * @phpstan-ignore-next-line
     * @throws
     *
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

        $this->beforeAction();

        $response = $this->action();

        $this->afterAction();

        return $response;
    }

    protected function clearActionStatus()
    {
        $this->parameterBag = null;
        $this->getIHKCStore()->clear();
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
     * @inheritDoc
     *
     * @throws
     *
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
     * @return Request|\Request|KIdeRequest
     *
     * @phpstan-ignore-next-line
     * @throws
     *
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
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->p(), $name)) {
            return call_user_func_array([$this->p(), $name], $arguments);
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
     * @deprecated It's a name change
     */
    protected function asJson(array $data = [], int $code = 200): JsonResponse
    {
        /** @phpstan-ignore-next-line */
        return $this->asResponse($data, $code);
    }

    protected function asResponse(array $data = [], int $code = 200): Response
    {
        return new JsonResponse([
            'code' => $code,
            'message' => 'ok',
            'data' => $data,
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
