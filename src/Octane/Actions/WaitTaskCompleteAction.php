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
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Psr\SimpleCache\InvalidArgumentException;

class WaitTaskCompleteAction
{
    use Action;

    /**
     * @return JsonResponse
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     * @throws PhpVersionNotSupportedException
     */
    public function action(): JsonResponse
    {
        $start = microtime(true);
        $workerCount = Octane::waitSwooleTasks();
        $end = microtime(true);

        $duration = $end - $start;
        $type = getenv('OCTANE_RUNTIME_TYPE') ?: 'unknown';
        $uri = $this->getRequest()->getRequestUri();

        Log::debug(sprintf('type:%s, uri:%s, workerCount:%s, duration%ss', $type, $uri, $workerCount, $duration));

        return $this->asJson([
            'duration' => ($end - $start)
        ]);
    }
}
