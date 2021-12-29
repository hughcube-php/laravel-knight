<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/8/12
 * Time: 00:08
 */

namespace HughCube\Laravel\Knight\Octane\Listeners;

use Laravel\Octane\Events\RequestReceived;

class PrepareServerVariables
{
    /**
     * Handle the event.
     *
     * @param  RequestReceived  $event
     * @return void
     */
    public function handle(mixed $event): void
    {
        /** 在事件触发器下面, 直接使用ip访问 */
        if ($host = parse_url(config('app.url'), PHP_URL_HOST)) {
            $event->request->server->set('HTTP_HOST', $host);
            $event->request->headers->set('HOST', $host);
        }
    }
}
