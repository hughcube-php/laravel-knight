<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/12
 * Time: 21:22.
 */

namespace HughCube\Laravel\Knight\Traits;

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

    protected function isSkipMultipleHandlerMethod(ReflectionMethod $method): bool
    {
        return false;
    }

    /**
     * @throws Throwable
     *
     * @return Collection<int, array<int, ReflectionMethod|Throwable|mixed>>
     */
    protected function triggerMultipleHandlers(bool $tryException = true): Collection
    {
        $results = Collection::empty();

        foreach ($this->getMultipleHandlers() as $method) {
            $result = $exception = null;

            try {
                $result = $this->{$method->name}();
            } catch (Throwable $exception) {
            }
            $results->add(['method' => $method, 'result' => $result, 'exception' => $exception]);

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
     * @return Collection<int, ReflectionMethod>
     */
    protected function getMultipleHandlers(): Collection
    {
        $handlers = Collection::empty();

        $reflection = new ReflectionClass($this);
        foreach ($reflection->getMethods() as $method) {
            /** 方法名必须包含Handler, 区分大小写 */
            $position = strrpos($method->name, 'Handler');
            if (false === $position) {
                continue;
            }

            /** 跳过指定方法不执行 */
            if ('getExceptionHandler' === $method->name || $this->isSkipMultipleHandlerMethod($method)) {
                continue;
            }

            /** 获取sort属性 */
            $sort = substr($method->name, $position + strlen('Handler'));
            if ('' !== $sort && !ctype_digit($sort)) {
                continue;
            }

            $handlers = $handlers->add(['method' => $method, 'sort' => intval($sort)]);
        }

        return $handlers->sortBy(function ($handler) {
            return $handler['sort'];
        })->pluck('method');
    }
}
