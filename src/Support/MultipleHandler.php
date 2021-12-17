<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/12/17
 * Time: 17:33
 */

namespace HughCube\Laravel\Knight\Support;

use Illuminate\Support\Str;
use Throwable;

trait MultipleHandler
{
    protected function skipHandlerException(): bool
    {
        return false;
    }

    protected function isStopHandlerResults($results): bool
    {
        return false === $results;
    }

    /**
     * @return mixed
     * @throws Throwable
     */
    protected function triggerHandlers(): mixed
    {
        $results = null;

        foreach ($this->getHandlers() as $handler) {
            $exception = null;
            try {
                $results = $this->{$handler}();
            } catch (Throwable $exception) {
            }

            if ($exception instanceof Throwable && $this->skipHandlerException()) {
                continue;
            }

            if ($exception instanceof Throwable && !$this->skipHandlerException()) {
                throw $exception;
            }

            if ($this->isStopHandlerResults($results)) {
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
