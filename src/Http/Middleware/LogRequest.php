<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/10/28
 * Time: 11:34.
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    public function handle(Request $request, callable $next): Response
    {
        Log::info(
            sprintf(
                'uri: %s, headers: %s, body: %s',
                $request->getUri(),
                json_encode($request->headers->all()),
                serialize($request->getContent())
            )
        );

        return $next($request);
    }
}
