<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/29
 * Time: 16:32.
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

if (class_exists(\Illuminate\Http\Middleware\HandleCors::class)) {
    abstract class HandleAllPathCorsBase extends \Illuminate\Http\Middleware\HandleCors
    {
    }
} elseif (class_exists(\Fruitcake\Cors\HandleCors::class)) {
    abstract class HandleAllPathCorsBase extends \Fruitcake\Cors\HandleCors
    {
    }
} else {
    abstract class HandleAllPathCorsBase
    {
        public function __construct(...$args)
        {
        }

        public function handle($request, Closure $next, ...$args)
        {
            return $next($request);
        }

        protected function hasMatchingPath(Request $request): bool
        {
            return true;
        }
    }
}

class HandleAllPathCors extends HandleAllPathCorsBase
{
    protected function hasMatchingPath(Request $request): bool
    {
        return true;
    }
}
