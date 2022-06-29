<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/6/29
 * Time: 16:32.
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected $optional = null;

    /**
     * @inheritDoc
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $exception = null;

        try {
            $this->authenticate($request, $guards);
        } catch (AuthenticationException $exception) {
        }

        if ($exception instanceof AuthenticationException && !$this->isOptional($request)) {
            throw $exception;
        }

        return $next($request);
    }

    protected function isOptional(Request $request): bool
    {
        return $request->is($this->getOptional()) || $request->fullUrlIs($this->getOptional());
    }

    protected function getOptional()
    {
        return ($this->optional ?: config('authenticate.optional')) ?: [];
    }
}
