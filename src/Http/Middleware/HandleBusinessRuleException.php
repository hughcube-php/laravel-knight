<?php

namespace HughCube\Laravel\Knight\Http\Middleware;

use Closure;
use HughCube\Laravel\Knight\Exceptions\BusinessRuleException;
use HughCube\Laravel\Knight\Exceptions\UserException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleBusinessRuleException
{
    /**
     * @throws UserException
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (BusinessRuleException $e) {
            throw new UserException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
