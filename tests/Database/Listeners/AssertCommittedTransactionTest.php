<?php

namespace HughCube\Laravel\Knight\Tests\Database\Listeners;

use HughCube\Laravel\Knight\Database\Listeners\AssertCommittedTransaction;
use HughCube\Laravel\Knight\Exceptions\NotCloseTransactionException;
use HughCube\Laravel\Knight\Tests\TestCase;

class AssertCommittedTransactionTest extends TestCase
{
    public function testHandlePassesWhenNoOpenTransaction()
    {
        $listener = new AssertCommittedTransaction();

        $this->assertNoException(function () use ($listener) {
            $listener->handle(null);
        });
    }

    public function testHandleThrowsWhenTransactionOpen()
    {
        $connection = $this->app->make('db')->connection();
        $connection->beginTransaction();

        $listener = new AssertCommittedTransaction();

        try {
            $listener->handle(null);
            $this->fail('Expected NotCloseTransactionException was not thrown.');
        } catch (NotCloseTransactionException $exception) {
            $this->assertStringContainsString('transaction is not closed', $exception->getMessage());
        } finally {
            while (method_exists($connection, 'transactionLevel') && $connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
        }
    }
}
