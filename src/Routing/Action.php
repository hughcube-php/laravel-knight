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
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Http\Request;
use Laravel\Lumen\Application as LumenApplication;

abstract class Action
{
    /**
     * @var LaravelRequest|LumenRequest
     */
    private $request;

    /**
     * @var ParameterBag
     */
    private $parameterBag;

    /**
     * @var array
     */
    private $attributes = [];

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

        if (app() instanceof LumenApplication) {
            return $this->request = app()->make(Request::class);
        }

        return $this->request = app()->make('request');
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
            $this->parameterBag = new ParameterBag(
                app(Factory::class)->validate($this->getRequest()->all(), $this->rules())
            );
        }
    }

    /**
     * Request rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [];
    }

    /**
     * The user builds virtual properties.
     *
     * return $this->getOrSetAttribute(__METHOD__, function (){
     *     return Model::findById($this->getParameter()->get('id'));
     * });
     *
     * @param mixed $name
     * @param callable $callable
     * @param bool $reset
     *
     * @return mixed
     */
    protected function getOrSetAttribute($name, $callable, $reset = false)
    {
        $key = md5(serialize($name));
        if (!array_key_exists($key, $this->attributes) || $reset) {
            $this->attributes[$key] = $callable();
        }

        return $this->attributes[$key];
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
