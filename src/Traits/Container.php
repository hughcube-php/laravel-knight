<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2022/3/11
 * Time: 19:08.
 */

namespace HughCube\Laravel\Knight\Traits;

use Illuminate\Config\Repository;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Events\Dispatcher as EventsDispatcher;
use Illuminate\Queue\QueueManager;

trait Container
{
    /**
     * @return IlluminateContainer
     */
    protected function getContainer(): IlluminateContainer
    {
        return IlluminateContainer::getInstance();
    }

    /**
     * @throws
     *
     * @return Dispatcher
     *
     * @phpstan-ignore-next-line
     */
    protected function getDispatcher(): Dispatcher
    {
        return $this->getContainer()->make(Dispatcher::class);
    }

    /**
     * @throws
     *
     * @return EventsDispatcher
     *
     * @phpstan-ignore-next-line
     */
    protected function getEventsDispatcher(): EventsDispatcher
    {
        return $this->getContainer()->make(EventsDispatcher::class);
    }

    /**
     * @throws
     *
     * @return QueueManager
     *
     * @phpstan-ignore-next-line
     */
    protected function getQueueManager(): QueueManager
    {
        return $this->getContainer()->make(QueueManager::class);
    }

    /**
     * @throws
     *
     * @return ExceptionHandler
     *
     * @phpstan-ignore-next-line
     */
    protected function getExceptionHandler(): ExceptionHandler
    {
        return $this->getContainer()->make(ExceptionHandler::class);
    }

    /**
     * @throws
     *
     * @return Repository
     *
     * @phpstan-ignore-next-line
     */
    protected function getContainerConfig(): Repository
    {
        return $this->getContainer()->make('config');
    }

    protected function isContainerDebug(): bool
    {
        return true == $this->getContainerConfig()->get('app.debug');
    }

    protected function isContainerEnv($env): bool
    {
        return $env === $this->getContainerConfig()->get('app.env', 'production');
    }

    protected function isContainerLocalEnv(): bool
    {
        return $this->isContainerEnv('local');
    }

    protected function isContainerTestEnv(): bool
    {
        return $this->isContainerEnv('local');
    }

    protected function isContainerProdEnv(): bool
    {
        return $this->isContainerEnv('production');
    }
}
