<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/24
 * Time: 15:56
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class HttpsGuard
{
    /**
     * The URIs that should be accessible while maintenance mode is enabled.
     *
     * @var array
     */
    protected array $except = [];

    /**
     * The application instance.
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Create a new middleware instance.
     *
     * @param  Application  $app
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param  Request  $request
     * @param  callable  $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($this->isEnable($request) && !$this->isExcept($request) && !$request->isSecure()) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }

    protected function isEnable(Request $request): bool
    {
        return $this->isSecureApplicationUrl()
            && $this->isHostRequest($request)
            && !$this->isAliYunFcHandler($request);
    }

    /**
     * Determine if the request has a URI that should be accessible in maintenance mode.
     *
     * @param  Request  $request
     * @return bool
     */
    protected function isExcept(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->fullUrlIs($except) || $request->is($except)) {
                return true;
            }
        }

        return false;
    }

    protected function isSecureApplicationUrl(): bool
    {
        $url = $this->app['config']->get('app.url');
        if (empty($url)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!is_string($scheme)) {
            return false;
        }

        return 'https' === strtolower($scheme);
    }

    protected function isHostRequest(Request $request): bool
    {
        return false === filter_var($request->getHost(), FILTER_VALIDATE_IP);
    }

    protected function isAliYunFcHandler(Request $request): bool
    {
        if (!$this->isRunInAliYunFc($request)) {
            return false;
        }

        $paths = ['initialize', 'invoke', 'pre-freeze', 'pre-stop'];
        return $request->fullUrlIs($paths) || $request->is($paths);
    }

    protected function isRunInAliYunFc(Request $request): bool
    {
        $fcHeaderCount = 0;
        foreach ($request->headers->all() as $name => $values) {
            if (Str::startsWith($name, 'x-fc-')) {
                $fcHeaderCount++;
            }
        }
        return $fcHeaderCount >= 5;
    }
}
