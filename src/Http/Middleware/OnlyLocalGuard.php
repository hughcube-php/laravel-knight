<?php

/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/8/31
 * Time: 18:21.
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Closure;
use HughCube\Laravel\Knight\Traits\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OnlyLocalGuard
{
    use Container;

    protected function allowIps(): array
    {
        $ips = ['127.0.0.1'];

        if ($this->isContainerLocalEnv()) {
            $ips[] = '0.0.0.0/0';
        }

        return $ips;
    }

    public function handle(Request $request, Closure $next)
    {
        if (!IpUtils::checkIp($request->getClientIp(), $this->allowIps())) {
            throw new AccessDeniedHttpException('Only allow local access!');
        }

        return $next($request);
    }
}
