<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/6/4
 * Time: 7:36 下午.
 */

namespace HughCube\Laravel\Knight\Tests\Routing;

use Closure;
use Illuminate\Http\Request;

class TestMiddleware
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'default')
    {
        $this->name = $name;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        ActionWithMiddleware::$middlewareExecutionLog[] = "middleware:{$this->name}:before";

        $response = $next($request);

        ActionWithMiddleware::$middlewareExecutionLog[] = "middleware:{$this->name}:after";

        return $response;
    }
}
