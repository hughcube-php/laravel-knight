<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/24
 * Time: 15:56
 */

namespace HughCube\Laravel\Knight\Http\Middleware;

use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpsGuard
{
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
        if ($this->isEnable() && !$request->isSecure()) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }

    protected function isEnable(): bool
    {
        return $this->isSecureApplicationUrl();
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
}
