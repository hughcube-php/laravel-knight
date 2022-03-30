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
use ReflectionClass;
use ReflectionMethod;
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
     * @return array
     */
    protected function triggerHandlers(bool $tryException = false, bool $logException = true): array
    {
        $results = [];
        foreach ($this->getHandlers() as $handler) {
            $result = $exception = null;

            try {
                $result = $this->{$handler}();
            } catch (Throwable $exception) {
            }
            $results[] = ['handler' => $handler, 'result' => $result, 'exception' => $exception];

            /** 抛出异常 */
            if ($exception instanceof Throwable && !$tryException) {
                throw $exception;
            }

            /** 记录异常 */
            if ($exception instanceof Throwable && $logException) {
                app(ExceptionHandler::class)->report($exception);
            }

            /** 是否终止执行 */
            if ($this->isStopHandlerResults($result, $exception)) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return string[]
     */
    protected function getHandlers(): array
    {
        $handlers = [];

        $reflection = new ReflectionClass($this);
        foreach ($reflection->getMethods() as $method) {
            $handler = $this->parseHandlerMethod($method);
            if (!is_array($handler) || !isset($handler[0], $handler[1])) {
                continue;
            }
            $handlers[$handler[0]] = $handler[1];
        }
        asort($handlers);

        return array_keys($handlers);
    }

    /**
     * @param ReflectionMethod $method
     *
     * @return null|array
     */
    protected function parseHandlerMethod(ReflectionMethod $method): ?array
    {
        if (!Str::is('*handler*', strtolower($method->name))) {
            return null;
        }

        $sort = Str::afterLast(strtolower($method->name), 'handler');
        if ('' !== $sort && !ctype_digit($sort)) {
            return null;
        }

        return [$method->name, intval(($sort ?: 0))];
    }
}
