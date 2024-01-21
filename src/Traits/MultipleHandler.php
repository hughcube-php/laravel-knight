<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/12
 * Time: 21:22.
 */

namespace HughCube\Laravel\Knight\Traits;

use HughCube\Laravel\Knight\Support\MultipleHandlerCallable;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

trait MultipleHandler
{
    use Container;

    protected function isStopMultipleHandlerResult($result, ?Throwable $exception = null): bool
    {
        //return null !== $result && !$exception instanceof Throwable;
        return false;
    }

    /**
     * @throws Throwable
     */
    protected function throwMultipleHandlerException(Throwable $exception): void
    {
        throw $exception;
    }

    /**
     * @throws Throwable
     */
    protected function reportMultipleHandlerException(Throwable $exception): void
    {
        $this->getExceptionHandler()->report($exception);
    }

    protected function isSkipMultipleHandler($name): bool
    {
        return false;
    }

    /**
     * @throws Throwable
     */
    protected function triggerMultipleHandlers(bool $tryException = true): array
    {
        $results = [];

        foreach ($this->getMultipleHandlers() as $handler) {
            $result = $exception = null;

            try {
                $result = call_user_func($handler->callable);
            } catch (Throwable $exception) {
            }
            $results[] = ['handler' => $handler, 'result' => $result, 'exception' => $exception];

            /** 记录异常或者抛出 */
            if ($exception instanceof Throwable && $tryException) {
                $this->reportMultipleHandlerException($exception);
            } elseif ($exception instanceof Throwable) {
                $this->throwMultipleHandlerException($exception);
            }

            /** 是否终止执行 */
            if ($this->isStopMultipleHandlerResult($result, $exception)) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return Collection<int, MultipleHandlerCallable>
     */
    protected function getMultipleHandlers(): Collection
    {
        $handlers = Collection::empty();

        $reflection = new ReflectionClass($this);
        foreach ($reflection->getMethods() as $method) {
            $handler = $this->parseMultipleHandlerMethod($method);
            if (!$handler instanceof MultipleHandlerCallable) {
                continue;
            }
            $handlers = $handlers->add($handler);
        }

        return $handlers
            ->sortBy(function (MultipleHandlerCallable $handlerCallable) {
                return $handlerCallable->sort;
            })
            ->values();
    }

    private function parseMultipleHandlerMethod(ReflectionMethod $method): ?MultipleHandlerCallable
    {
        if (/**  */
            in_array($method->name, ['getExceptionHandler', 'isSkipMultipleHandler'])
            || $this->isSkipMultipleHandler($method->name)
        ) {
            return null;
        }

        $position = strrpos($method->name, 'Handler');
        if (false === $position) {
            return null;
        }

        $sort = substr($method->name, $position + strlen('Handler'));
        if ('' !== $sort && !ctype_digit(ltrim($sort, '0'))) {
            return null;
        }

        return new MultipleHandlerCallable(intval($sort ?: '0'), [$this, $method->name]);
    }
}
