<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/1/31
 * Time: 11:46.
 */

namespace HughCube\Laravel\Knight\Database\Listeners;

use HughCube\Laravel\Knight\Exceptions\NotCloseTransactionException;
use HughCube\Laravel\Knight\Traits\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

class AssertCommittedTransaction
{
    use Container;

    /**
     * @throws BindingResolutionException
     *
     * @return void
     */
    public function handle($event)
    {
        foreach ($this->getConnections() as $name => $connection) {
            if ($connection->transactionLevel() > 0) {
                throw new NotCloseTransactionException(sprintf("Connection '%s' transaction is not closed!", $name));
            }
        }
    }

    /**
     * @throws BindingResolutionException
     *
     * @return array<string, Connection>
     */
    protected function getConnections(): array
    {
        return $this->getDatabaseManager()->getConnections();
    }

    /**
     * @throws BindingResolutionException
     */
    protected function getDatabaseManager(): DatabaseManager
    {
        return $this->getContainer()->make('db');
    }
}
