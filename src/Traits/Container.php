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
     * @return Dispatcher
     * @phpstan-ignore-next-line
     * @throws
     */
    protected function getDispatcher(): Dispatcher
    {
        return $this->getContainer()->make(Dispatcher::class);
    }

    /**
     * @return QueueManager
     * @phpstan-ignore-next-line
     * @throws
     */
    protected function getQueueManager(): QueueManager
    {
        return $this->getContainer()->make(QueueManager::class);
    }

    /**
     * @return Repository
     * @phpstan-ignore-next-line
     * @throws
     *
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
