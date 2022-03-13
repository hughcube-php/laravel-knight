<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/12
 * Time: 21:22.
 */

namespace HughCube\Laravel\Knight\Traits;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Str;
use Throwable;

trait MultipleHandler
{
    protected function isStopHandlerResults($results, Throwable $exception = null): bool
    {
        return null !== $results && !$exception instanceof Throwable;
    }

    /**
     * @param bool $tryException
     * @param bool $logException
     *
     * @throws Throwable
     *
     * @return mixed
     */
    protected function triggerHandlers(bool $tryException = false, bool $logException = true)
    {
        $results = null;

        foreach ($this->getHandlers() as $handler) {
            $exception = null;

            try {
                $results = $this->{$handler}();
            } catch (Throwable $exception) {
            }

            /** 抛出异常 */
            if ($exception instanceof Throwable && !$tryException) {
                throw $exception;
            }

            /** 记录异常 */
            if ($exception instanceof Throwable && $logException) {
                app(ExceptionHandler::class)->report($exception);
            }

            /** 是否终止执行 */
            if ($this->isStopHandlerResults($results, $exception)) {
                return $results;
            }
        }

        return $results;
    }

    /**
     * @return array
     */
    protected function getHandlers(): array
    {
        $handlers = [];
        foreach (get_class_methods($this) as $method) {
            if (!Str::contains(strtolower($method), 'handler')) {
                continue;
            }

            $sort = Str::afterLast(strtolower($method), 'handler');
            if ('' !== $sort && !is_numeric($sort)) {
                continue;
            }

            $handlers[$method] = intval($sort);
        }
        asort($handlers);

        return array_keys($handlers);
    }
}
