<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/31
 * Time: 18:21.
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Closure;
use HughCube\Laravel\Knight\Support\Str;
use HughCube\Laravel\Knight\Traits\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OnlyPrivateIpGuard
{
    use Container;

    public function handle(Request $request, Closure $next)
    {
        if (!Str::isPrivateIp($request->getClientIp())) {
            throw new AccessDeniedHttpException('Only allow specified intranet ip access!');
        }

        return $next($request);
    }
}
