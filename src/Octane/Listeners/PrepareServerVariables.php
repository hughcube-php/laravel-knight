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
        $this->prepareHost($event);
    }

    /**
     * @param  RequestReceived  $event
     * @return void
     */
    protected function prepareHost(mixed $event)
    {
        if (!$event instanceof RequestReceived) {
            return;
        }

        $host = $event->request->getHost();
        if (false === filter_var($host, FILTER_VALIDATE_IP)) {
            return;
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST);
        if (empty($host)) {
            return;
        }

        $event->request->server->set('HTTP_HOST', $host);
        $event->request->headers->set('HOST', $host);
    }
}
