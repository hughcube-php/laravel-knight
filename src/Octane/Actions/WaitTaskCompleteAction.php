<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 21:36
 */

namespace HughCube\Laravel\Knight\Octane\Actions;

use HughCube\Laravel\Knight\Octane\Octane;
use HughCube\Laravel\Knight\Routing\Action;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;
use Swoole\Http\Server;
use Throwable;

class WaitTaskCompleteAction
{
    use Action;

    /**
     * @return JsonResponse
     * @throws BindingResolutionException
     */
    public function action(): JsonResponse
    {
        $workerCount = $this->getWorkerCount();

        $start = microtime(true);
        Octane::waitTasks($workerCount * 3);
        $end = microtime(true);

        $duration = $end - $start;
        $type = getenv('OCTANE_RUNTIME_TYPE') ?: 'unknown';
        $uri = $this->getRequest()->getRequestUri();

        Log::debug(sprintf('type:%s, uri:%s, workerCount:%s, duration%ss', $type, $uri, $workerCount, $duration));

        return $this->asJson([
            'duration' => ($end - $start)
        ]);
    }

    /**
     * @return int
     */
    protected function getWorkerCount(): int
    {
        if (!class_exists(Server::class)) {
            return 0;
        }

        if (!app()->bound(Server::class)) {
            return 0;
        }

        return app(Server::class)->setting['task_worker_num'] ?? 0;
    }
}
